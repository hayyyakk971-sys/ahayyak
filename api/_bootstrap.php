<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/session_handler.php';

// ── Session config (must happen before session_start) ──────────────────────
$isSecure = str_starts_with(APP_URL, 'https');
session_set_cookie_params([
    'lifetime' => 0,
    'path'     => '/',
    'secure'   => $isSecure,
    'httponly' => true,
    'samesite' => 'Strict',
]);
if (session_status() === PHP_SESSION_NONE) {
    // Use DB-backed sessions so they survive Railway's ephemeral filesystem
    session_set_save_handler(new DbSessionHandler(getDB()), true);
    session_start();
}

// ── CORS headers ───────────────────────────────────────────────────────────
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
// Allow same-origin requests (Railway serves frontend + API from same domain)
header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

// ── Helper functions ───────────────────────────────────────────────────────

function jsonOk(mixed $data): never {
    echo json_encode(['ok' => true, 'data' => $data], JSON_UNESCAPED_UNICODE);
    exit;
}

function jsonError(string $message, int $status = 400): never {
    http_response_code($status);
    echo json_encode(['ok' => false, 'error' => $message], JSON_UNESCAPED_UNICODE);
    exit;
}

function requireMethod(string ...$methods): void {
    if (!in_array($_SERVER['REQUEST_METHOD'], $methods, true)) {
        jsonError('Method not allowed', 405);
    }
}

function requireAuth(): array {
    if (empty($_SESSION['user_id'])) {
        jsonError('Not authenticated', 401);
    }
    return ['id' => $_SESSION['user_id'], 'role' => $_SESSION['role']];
}

function requireRole(string ...$roles): array {
    $user = requireAuth();
    if (!in_array($user['role'], $roles, true)) {
        jsonError('Forbidden', 403);
    }
    return $user;
}

function verifyCsrf(): void {
    $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
        jsonError('Invalid CSRF token', 403);
    }
}

function requireCsrf(): void {
    $method = $_SERVER['REQUEST_METHOD'];
    if (in_array($method, ['POST', 'PUT', 'DELETE', 'PATCH'], true)) {
        verifyCsrf();
    }
}

function bodyJson(): array {
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

function sanitizeInt(mixed $v): ?int {
    return filter_var($v, FILTER_VALIDATE_INT) !== false ? (int)$v : null;
}
