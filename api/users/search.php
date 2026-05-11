<?php
require_once __DIR__ . '/../_bootstrap.php';
requireMethod('GET');
requireAuth();

$q = trim($_GET['q'] ?? '');
if (strlen($q) < 2) jsonOk([]);

$db   = getDB();
$like = '%' . $q . '%';
$stmt = $db->prepare(
    'SELECT id, name_ar, name_en, emirate, avatar_color
     FROM users
     WHERE role = "user" AND (name_ar LIKE :q OR name_en LIKE :q OR email LIKE :q)
     LIMIT 20'
);
$stmt->execute([':q' => $like]);
jsonOk($stmt->fetchAll());
