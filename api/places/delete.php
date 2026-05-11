<?php
require_once __DIR__ . '/../_bootstrap.php';
requireMethod('DELETE');
$user = requireRole('place_owner');
requireCsrf();

$body = bodyJson();
$id   = sanitizeInt($body['id'] ?? null);
if (!$id) jsonError('id required');

$db   = getDB();
$stmt = $db->prepare('SELECT owner_id FROM places WHERE id = :id');
$stmt->execute([':id' => $id]);
$place = $stmt->fetch();
if (!$place) jsonError('Place not found', 404);
if ((int)$place['owner_id'] !== $user['id']) jsonError('Forbidden', 403);

$db->prepare('DELETE FROM places WHERE id = :id')->execute([':id' => $id]);
jsonOk(['deleted' => true]);
