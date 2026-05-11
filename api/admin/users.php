<?php
require_once __DIR__ . '/../_bootstrap.php';
requireMethod('GET');
requireRole('admin');

$db   = getDB();
$stmt = $db->prepare(
    'SELECT u.id, u.email, u.role, u.name_ar, u.name_en, u.emirate,
            JSON_LENGTH(IFNULL(u.unlocked_achievements, "[]")) AS achievements_count,
            u.created_at,
            COALESCE(SUM(p.status = "approved"), 0)  AS places_approved,
            COALESCE(SUM(p.status = "pending"),  0)  AS places_pending,
            COALESCE(SUM(p.status = "rejected"), 0)  AS places_rejected
     FROM users u
     LEFT JOIN places p ON p.owner_id = u.id
     GROUP BY u.id
     ORDER BY u.created_at DESC'
);
$stmt->execute();
$rows = $stmt->fetchAll();
// Cast count columns to int
foreach ($rows as &$r) {
    $r['achievements_count'] = (int)$r['achievements_count'];
    $r['places_approved']    = (int)$r['places_approved'];
    $r['places_pending']     = (int)$r['places_pending'];
    $r['places_rejected']    = (int)$r['places_rejected'];
}
jsonOk($rows);
