<?php
require_once __DIR__ . '/../_bootstrap.php';
requireMethod('GET');
$db = getDB();

$id = sanitizeInt($_GET['id'] ?? null);
if (!$id) jsonError('id required');

$stmt = $db->prepare(
    'SELECT p.*, u.name_ar AS owner_name_ar, u.name_en AS owner_name_en
     FROM places p JOIN users u ON u.id = p.owner_id
     WHERE p.id = :id'
);
$stmt->execute([':id' => $id]);
$place = $stmt->fetch();
if (!$place) jsonError('Place not found', 404);

// Decode JSON columns
foreach (['phobia_triggers','medical_triggers','interest_tags'] as $col) {
    $place[$col] = $place[$col] ? json_decode($place[$col], true) : [];
}

// Fetch menu items
$mStmt = $db->prepare('SELECT * FROM menu_items WHERE place_id = :pid AND is_available = 1');
$mStmt->execute([':pid' => $id]);
$items = $mStmt->fetchAll();
foreach ($items as &$item) {
    foreach (['allergens','phobia_triggers','medical_triggers'] as $col) {
        $item[$col] = $item[$col] ? json_decode($item[$col], true) : [];
    }
}

$place['menu'] = $items;
jsonOk($place);
