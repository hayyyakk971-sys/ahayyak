<?php
require_once __DIR__ . '/../_bootstrap.php';
requireMethod('GET', 'PUT');
$user = requireAuth();
$db   = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $stmt = $db->prepare('SELECT unlocked_achievements FROM users WHERE id = :id');
    $stmt->execute([':id' => $user['id']]);
    $row = $stmt->fetch();
    $unlocked = $row && $row['unlocked_achievements'] ? json_decode($row['unlocked_achievements'], true) : [];
    jsonOk(['unlocked' => $unlocked]);
}

requireCsrf();
$body    = bodyJson();
$unlocked = $body['unlocked'] ?? [];
if (!is_array($unlocked)) jsonError('unlocked must be an array');

$db->prepare('UPDATE users SET unlocked_achievements = :val WHERE id = :id')
   ->execute([':val' => json_encode(array_values(array_unique($unlocked))), ':id' => $user['id']]);

jsonOk(['updated' => true]);
