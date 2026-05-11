<?php
require_once __DIR__ . '/../_bootstrap.php';
requireMethod('GET');

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
jsonOk(['token' => $_SESSION['csrf_token']]);
