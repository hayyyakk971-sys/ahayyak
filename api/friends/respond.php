<?php
require_once __DIR__ . '/../_bootstrap.php';
requireMethod('POST');
$user = requireAuth();
requireCsrf();

$body         = bodyJson();
$friendshipId = sanitizeInt($body['friendship_id'] ?? null);
$action       = $body['action'] ?? '';

if (!$friendshipId) jsonError('friendship_id required');
if (!in_array($action, ['accept', 'decline'], true)) jsonError('action must be accept or decline');

$db   = getDB();
$stmt = $db->prepare('SELECT * FROM friendships WHERE id = :id');
$stmt->execute([':id' => $friendshipId]);
$f = $stmt->fetch();

if (!$f) jsonError('Friendship not found', 404);
// Only the recipient (not the sender) can respond
if ($f['requested_by'] === $user['id']) jsonError('Cannot respond to your own request', 403);
if ($f['user1_id'] !== $user['id'] && $f['user2_id'] !== $user['id']) jsonError('Forbidden', 403);

$newStatus = $action === 'accept' ? 'accepted' : 'declined';
$db->prepare('UPDATE friendships SET status = :s WHERE id = :id')
   ->execute([':s' => $newStatus, ':id' => $friendshipId]);

jsonOk(['status' => $newStatus]);
