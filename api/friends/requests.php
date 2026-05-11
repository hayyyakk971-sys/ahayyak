<?php
require_once __DIR__ . '/../_bootstrap.php';
requireMethod('GET');
$user = requireAuth();
$db   = getDB();

// Return pending requests where current user is the recipient (not the sender)
$stmt = $db->prepare(
    'SELECT f.id AS friendship_id, u.id, u.name_ar, u.name_en, u.emirate, u.avatar_color
     FROM friendships f
     JOIN users u ON u.id = f.requested_by
     WHERE f.status = "pending"
       AND f.requested_by <> :uid
       AND (f.user1_id = :uid OR f.user2_id = :uid)'
);
$stmt->execute([':uid' => $user['id']]);
jsonOk($stmt->fetchAll());
