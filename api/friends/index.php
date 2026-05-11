<?php
require_once __DIR__ . '/../_bootstrap.php';
requireMethod('GET', 'POST');
$user = requireAuth();
$db   = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $stmt = $db->prepare(
        'SELECT u.id, u.name_ar, u.name_en, u.emirate, u.avatar_color,
                f.id AS friendship_id, f.status
         FROM friendships f
         JOIN users u ON (u.id = IF(f.user1_id = :uid, f.user2_id, f.user1_id))
         WHERE (f.user1_id = :uid OR f.user2_id = :uid)
           AND f.status = "accepted"'
    );
    $stmt->execute([':uid' => $user['id']]);
    jsonOk($stmt->fetchAll());
}

// POST — send friend request
requireCsrf();
$body     = bodyJson();
$targetId = sanitizeInt($body['target_user_id'] ?? null);
if (!$targetId || $targetId === $user['id']) jsonError('Invalid target user');

// Ensure canonical ordering: smaller id = user1
$u1 = min($user['id'], $targetId);
$u2 = max($user['id'], $targetId);

// Check for existing relationship
$stmt = $db->prepare('SELECT id, status FROM friendships WHERE user1_id = :u1 AND user2_id = :u2');
$stmt->execute([':u1' => $u1, ':u2' => $u2]);
$existing = $stmt->fetch();
if ($existing) {
    if ($existing['status'] === 'blocked') jsonError('Cannot send request', 403);
    jsonError('Request already exists', 409);
}

$db->prepare(
    'INSERT INTO friendships (user1_id, user2_id, status, requested_by)
     VALUES (:u1, :u2, "pending", :req)'
)->execute([':u1' => $u1, ':u2' => $u2, ':req' => $user['id']]);

jsonOk(['sent' => true]);
