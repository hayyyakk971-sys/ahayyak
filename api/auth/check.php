<?php
require_once __DIR__ . '/../_bootstrap.php';
requireMethod('GET');

if (empty($_SESSION['user_id'])) {
    jsonOk(['logged_in' => false]);
}

$db   = getDB();
$stmt = $db->prepare('SELECT id, email, role, name_ar, name_en, language, theme, avatar_color FROM users WHERE id = :id');
$stmt->execute([':id' => $_SESSION['user_id']]);
$user = $stmt->fetch();

if (!$user) {
    session_destroy();
    jsonOk(['logged_in' => false]);
}

jsonOk(['logged_in' => true, 'user' => $user]);
