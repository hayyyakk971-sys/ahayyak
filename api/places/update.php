<?php
require_once __DIR__ . '/../_bootstrap.php';
requireMethod('PUT');
$user = requireRole('place_owner');
requireCsrf();

$body = bodyJson();
$id   = sanitizeInt($body['id'] ?? null);
if (!$id) jsonError('id required');

$db   = getDB();
$stmt = $db->prepare('SELECT id, owner_id FROM places WHERE id = :id');
$stmt->execute([':id' => $id]);
$place = $stmt->fetch();
if (!$place) jsonError('Place not found', 404);
if ((int)$place['owner_id'] !== $user['id']) jsonError('Forbidden', 403);

$allowed = ['name_ar','name_en','city','emirate','address_ar','address_en','latitude','longitude',
            'description_ar','description_en','opening_hours','price_range',
            'phobia_triggers','medical_triggers','interest_tags'];

$sets   = ['status = "pending"', 'rejection_reason = NULL'];
$params = [':id' => $id];

foreach ($allowed as $field) {
    if (!array_key_exists($field, $body)) continue;
    $val = $body[$field];
    if (in_array($field, ['phobia_triggers','medical_triggers','interest_tags'], true)) {
        $val = json_encode($val, JSON_UNESCAPED_UNICODE);
    }
    $sets[]            = "`$field` = :$field";
    $params[":$field"] = $val;
}

if (count($sets) <= 2) jsonError('Nothing to update');

$db->prepare('UPDATE places SET ' . implode(', ', $sets) . ' WHERE id = :id')->execute($params);
jsonOk(['updated' => true, 'status' => 'pending']);
