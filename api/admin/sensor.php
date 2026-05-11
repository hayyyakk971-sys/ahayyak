<?php
require_once __DIR__ . '/../_bootstrap.php';
requireMethod('GET');
requireRole('admin');

$db   = getDB();
$stmt = $db->prepare(
    'SELECT * FROM sensor_readings ORDER BY recorded_at DESC LIMIT 100'
);
$stmt->execute();
jsonOk($stmt->fetchAll());
