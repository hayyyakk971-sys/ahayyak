<?php
require_once __DIR__ . '/../_bootstrap.php';
requireMethod('POST');
requireCsrf();

$body = bodyJson();
$email    = trim($body['email'] ?? '');
$password = $body['password'] ?? '';
$role     = $body['role'] ?? 'user';
$nameAr   = trim($body['name_ar'] ?? '');
$nameEn   = trim($body['name_en'] ?? '');
$language = in_array($body['language'] ?? '', ['ar', 'en'], true) ? $body['language'] : 'ar';

// Validate
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    jsonError('Invalid email address');
}
if (strlen($password) < 8) {
    jsonError('Password must be at least 8 characters');
}
if (!in_array($role, ['user', 'place_owner'], true)) {
    jsonError('Invalid role');
}

$db = getDB();

// Check duplicate email
$stmt = $db->prepare('SELECT id FROM users WHERE email = :email');
$stmt->execute([':email' => $email]);
if ($stmt->fetch()) {
    jsonError('Email already registered', 409);
}

$hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);

$stmt = $db->prepare(
    'INSERT INTO users (email, password_hash, role, name_ar, name_en, language)
     VALUES (:email, :hash, :role, :name_ar, :name_en, :language)'
);
$stmt->execute([
    ':email'   => $email,
    ':hash'    => $hash,
    ':role'    => $role,
    ':name_ar' => $nameAr ?: null,
    ':name_en' => $nameEn ?: null,
    ':language'=> $language,
]);

$userId = (int)$db->lastInsertId();

// Generate CSRF token and start session
$_SESSION['user_id']    = $userId;
$_SESSION['role']       = $role;
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));

jsonOk([
    'id'       => $userId,
    'email'    => $email,
    'role'     => $role,
    'language' => $language,
]);
