<?php
require_once __DIR__ . '/../_bootstrap.php';
requireMethod('GET', 'PUT');
$user = requireAuth();
$db   = getDB();

$friendId = isset($_GET['friend_id']) ? sanitizeInt($_GET['friend_id']) : null;

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $stmt = $db->prepare(
        'SELECT * FROM privacy_settings
         WHERE owner_user_id = :uid AND target_friend_id ' .
        ($friendId ? '= :fid' : 'IS NULL')
    );
    $params = [':uid' => $user['id']];
    if ($friendId) $params[':fid'] = $friendId;
    $stmt->execute($params);
    $row = $stmt->fetch();

    if (!$row) {
        // Return defaults
        jsonOk([
            'share_health_conditions' => 0, 'share_food_preferences' => 0,
            'share_phobias' => 0, 'share_allergies' => 0,
            'share_bio' => 1, 'share_avatar' => 1,
            'share_emirate_location' => 1, 'share_contact_info' => 0,
        ]);
    }
    jsonOk($row);
}

requireCsrf();
$body = bodyJson();
$fields = ['share_health_conditions','share_food_preferences','share_phobias',
           'share_allergies','share_bio','share_avatar','share_emirate_location','share_contact_info'];

$sets   = [];
$params = [':uid' => $user['id'], ':fid' => $friendId];

foreach ($fields as $f) {
    if (array_key_exists($f, $body)) {
        $sets[] = "`$f` = :$f";
        $params[":$f"] = $body[$f] ? 1 : 0;
    }
}
if (empty($sets)) jsonError('Nothing to update');

// Upsert
$db->prepare(
    'INSERT INTO privacy_settings (owner_user_id, target_friend_id, ' . implode(', ', $fields) . ')
     VALUES (:uid, :fid, ' . implode(', ', array_map(fn($f) => ":$f", $fields)) . ')
     ON DUPLICATE KEY UPDATE ' . implode(', ', $sets)
)->execute(array_merge($params, array_combine(
    array_map(fn($f) => ":$f", $fields),
    array_map(fn($f) => isset($body[$f]) ? ($body[$f] ? 1 : 0) : 0, $fields)
)));

jsonOk(['updated' => true]);
