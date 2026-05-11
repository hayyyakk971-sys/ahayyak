<?php
require_once __DIR__ . '/../_bootstrap.php';
requireMethod('PUT', 'DELETE');
$user = requireRole('place_owner');
requireCsrf();

$body   = bodyJson();
$itemId = sanitizeInt($body['id'] ?? null);
if (!$itemId) jsonError('id required');

$db   = getDB();
$stmt = $db->prepare(
    'SELECT mi.id, p.owner_id FROM menu_items mi JOIN places p ON p.id = mi.place_id WHERE mi.id = :id'
);
$stmt->execute([':id' => $itemId]);
$row = $stmt->fetch();
if (!$row) jsonError('Item not found', 404);
if ((int)$row['owner_id'] !== $user['id']) jsonError('Forbidden', 403);

if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    $db->prepare('DELETE FROM menu_items WHERE id = :id')->execute([':id' => $itemId]);
    jsonOk(['deleted' => true]);
}

// PUT — update item
$allowed = ['name_ar','name_en','description_ar','description_en','price','category','is_available'];
$jsonCols = ['allergens','phobia_triggers','medical_triggers'];
$sets     = [];
$params   = [':id' => $itemId];

foreach (array_merge($allowed, $jsonCols) as $field) {
    if (!array_key_exists($field, $body)) continue;
    $val = $body[$field];
    if (in_array($field, $jsonCols, true)) {
        $val = json_encode($val, JSON_UNESCAPED_UNICODE);
    }
    if ($field === 'is_available') $val = $val ? 1 : 0;
    $sets[]            = "`$field` = :$field";
    $params[":$field"] = $val;
}

if (empty($sets)) jsonError('Nothing to update');
$db->prepare('UPDATE menu_items SET ' . implode(', ', $sets) . ' WHERE id = :id')->execute($params);
jsonOk(['updated' => true]);
