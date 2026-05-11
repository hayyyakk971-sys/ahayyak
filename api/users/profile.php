<?php
require_once __DIR__ . '/../_bootstrap.php';
requireMethod('GET', 'PUT');
$user = requireAuth();
$db   = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $stmt = $db->prepare('SELECT id, email, role, name_ar, name_en, dob, status, emirate,
        latitude, longitude, language, theme, is_healthy, phobias, allergies,
        food_preferences, medical_conditions, interests, unlocked_achievements,
        avatar_data, avatar_color, bio, created_at
        FROM users WHERE id = :id');
    $stmt->execute([':id' => $user['id']]);
    $row = $stmt->fetch();
    if (!$row) jsonError('User not found', 404);

    // Decode JSON columns
    foreach (['phobias','allergies','food_preferences','medical_conditions','interests','unlocked_achievements'] as $col) {
        $row[$col] = $row[$col] ? json_decode($row[$col], true) : [];
    }
    jsonOk($row);
}

// PUT — update profile
requireCsrf();
$body = bodyJson();

$allowed = ['name_ar','name_en','dob','status','emirate','latitude','longitude',
            'language','theme','is_healthy','phobias','allergies','food_preferences',
            'medical_conditions','interests','avatar_color','bio'];

$sets  = [];
$params = [':id' => $user['id']];

foreach ($allowed as $field) {
    if (!array_key_exists($field, $body)) continue;
    $val = $body[$field];
    // JSON fields
    if (in_array($field, ['phobias','allergies','food_preferences','medical_conditions','interests'], true)) {
        $val = json_encode($val, JSON_UNESCAPED_UNICODE);
    }
    if ($field === 'language' && !in_array($val, ['ar','en'], true)) continue;
    if ($field === 'is_healthy') $val = $val ? 1 : 0;
    $sets[]          = "`$field` = :$field";
    $params[":$field"] = $val;
}

if (empty($sets)) jsonError('Nothing to update');

$db->prepare('UPDATE users SET ' . implode(', ', $sets) . ' WHERE id = :id')->execute($params);
jsonOk(['updated' => true]);
