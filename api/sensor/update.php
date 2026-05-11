<?php
require_once __DIR__ . '/../_bootstrap.php';
requireMethod('POST');

// ESP32 posts without a browser session — authenticate with a shared secret instead
$secret = getenv('SENSOR_SECRET') ?: '';
$provided = $_SERVER['HTTP_X_SENSOR_SECRET'] ?? ($_GET['secret'] ?? '');
if ($secret && !hash_equals($secret, $provided)) {
    jsonError('Unauthorized', 401);
}

$body = bodyJson();
// Also support form-encoded (ESP32 may send application/x-www-form-urlencoded)
if (empty($body)) $body = $_POST;

$temp       = isset($body['temperature']) ? (float)$body['temperature'] : null;
$humidity   = isset($body['humidity']) ? (float)$body['humidity'] : null;
$aqi        = isset($body['aqi']) ? (int)$body['aqi'] : null;
$placeId    = isset($body['place_id']) ? sanitizeInt($body['place_id']) : null;
$crowd      = isset($body['overcrowding_percent']) ? (int)$body['overcrowding_percent'] : null;

$aqiLevel = match(true) {
    $aqi === null          => null,
    $aqi <= 50             => 'Good',
    $aqi <= 100            => 'Moderate',
    $aqi <= 150            => 'Unhealthy for Sensitive',
    $aqi <= 200            => 'Unhealthy',
    $aqi <= 300            => 'Very Unhealthy',
    default                => 'Hazardous',
};
$crowdLevel = match(true) {
    $crowd === null => null,
    $crowd < 30    => 'Low',
    $crowd < 60    => 'Moderate',
    $crowd < 85    => 'High',
    default        => 'Very High',
};

$db = getDB();
$db->prepare(
    'INSERT INTO sensor_readings
     (temperature, humidity, aqi, air_quality_level, overcrowding_percent, overcrowding_level, place_id)
     VALUES (:temp, :hum, :aqi, :aqil, :crowd, :crowdl, :pid)'
)->execute([
    ':temp'   => $temp,
    ':hum'    => $humidity,
    ':aqi'    => $aqi,
    ':aqil'   => $aqiLevel,
    ':crowd'  => $crowd,
    ':crowdl' => $crowdLevel,
    ':pid'    => $placeId,
]);

jsonOk(['recorded' => true]);
