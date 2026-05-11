<?php
require_once __DIR__ . '/../_bootstrap.php';
requireMethod('POST');
requireCsrf();

$body     = bodyJson();
$email    = trim($body['email'] ?? '');
$password = $body['password'] ?? '';

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    jsonError('Invalid email address');
}
if ($password === '') {
    jsonError('Password required');
}

$db   = getDB();
$stmt = $db->prepare('SELECT id, password_hash, role, name_ar, name_en, language, theme, avatar_color FROM users WHERE email = :email');
$stmt->execute([':email' => $email]);
$user = $stmt->fetch();

// Constant-time comparison to prevent timing attacks
if (!$user || !password_verify($password, $user['password_hash'])) {
    jsonError('Invalid email or password', 401);
}

$_SESSION['user_id']    = (int)$user['id'];
$_SESSION['role']       = $user['role'];
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));

unset($user['password_hash']);
jsonOk($user);
