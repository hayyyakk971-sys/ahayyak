<?php
require_once __DIR__ . '/../_bootstrap.php';
requireMethod('POST');
$user = requireAuth();
requireCsrf();

$body = bodyJson();
$avatarData = $body['avatar'] ?? '';

if (empty($avatarData)) jsonError('No avatar data provided');
// Sanity check: must be a base64 data URI or plain base64
if (strlen($avatarData) > 500000) jsonError('Avatar data too large');

$db = getDB();
$db->prepare('UPDATE users SET avatar_data = :avatar WHERE id = :id')
   ->execute([':avatar' => $avatarData, ':id' => $user['id']]);

jsonOk(['updated' => true]);
