<?php
require_once __DIR__ . '/../_bootstrap.php';
requireMethod('DELETE');
$user = requireAuth();
requireCsrf();

$body     = bodyJson();
$friendId = sanitizeInt($body['friend_id'] ?? null);
if (!$friendId) jsonError('friend_id required');

$u1 = min($user['id'], $friendId);
$u2 = max($user['id'], $friendId);

$db = getDB();
$db->prepare('DELETE FROM friendships WHERE user1_id = :u1 AND user2_id = :u2')
   ->execute([':u1' => $u1, ':u2' => $u2]);

jsonOk(['removed' => true]);
