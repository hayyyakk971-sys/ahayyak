<?php
require_once __DIR__ . '/../_bootstrap.php';
requireMethod('GET');

$db   = getDB();
$stmt = $db->prepare(
    'SELECT temperature, humidity, aqi, air_quality_level,
            overcrowding_percent, overcrowding_level, recorded_at
     FROM sensor_readings ORDER BY recorded_at DESC LIMIT 1'
);
$stmt->execute();
$row = $stmt->fetch();
if (!$row) jsonOk(null);
jsonOk($row);
