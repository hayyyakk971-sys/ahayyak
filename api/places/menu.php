<?php
require_once __DIR__ . '/../_bootstrap.php';
requireMethod('GET', 'POST');
$db = getDB();

$placeId = sanitizeInt($_GET['place_id'] ?? null);

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (!$placeId) jsonError('place_id required');
    $stmt = $db->prepare('SELECT * FROM menu_items WHERE place_id = :pid ORDER BY id');
    $stmt->execute([':pid' => $placeId]);
    $items = $stmt->fetchAll();
    foreach ($items as &$item) {
        foreach (['allergens','phobia_triggers','medical_triggers'] as $col) {
            $item[$col] = $item[$col] ? json_decode($item[$col], true) : [];
        }
    }
    jsonOk($items);
}

// POST — owner adds menu item
$user = requireRole('place_owner');
requireCsrf();
$body    = bodyJson();
$placeId = sanitizeInt($body['place_id'] ?? null);
if (!$placeId) jsonError('place_id required');

// Verify ownership
$stmt = $db->prepare('SELECT owner_id FROM places WHERE id = :id');
$stmt->execute([':id' => $placeId]);
$place = $stmt->fetch();
if (!$place) jsonError('Place not found', 404);
if ((int)$place['owner_id'] !== $user['id']) jsonError('Forbidden', 403);

$validCats = ['Starter','Main','Dessert','Drink','Other'];
$itemCat   = in_array($body['category'] ?? '', $validCats, true) ? $body['category'] : 'Other';

$stmt = $db->prepare(
    'INSERT INTO menu_items
     (place_id, name_ar, name_en, description_ar, description_en, price, category,
      allergens, phobia_triggers, medical_triggers, is_available)
     VALUES
     (:pid, :nar, :nen, :dar, :den, :price, :cat,
      :allergens, :phobia, :medical, 1)'
);
$stmt->execute([
    ':pid'      => $placeId,
    ':nar'      => $body['name_ar'] ?? null,
    ':nen'      => $body['name_en'] ?? null,
    ':dar'      => $body['description_ar'] ?? null,
    ':den'      => $body['description_en'] ?? null,
    ':price'    => isset($body['price']) ? (float)$body['price'] : null,
    ':cat'      => $itemCat,
    ':allergens'=> isset($body['allergens']) ? json_encode($body['allergens']) : null,
    ':phobia'   => isset($body['phobia_triggers']) ? json_encode($body['phobia_triggers']) : null,
    ':medical'  => isset($body['medical_triggers']) ? json_encode($body['medical_triggers']) : null,
]);

jsonOk(['id' => (int)$db->lastInsertId()]);
