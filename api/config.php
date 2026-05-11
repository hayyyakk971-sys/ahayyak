<?php
// All sensitive values come from environment variables (set in Railway Variables tab)
define('OPENROUTER_API_KEY', getenv('OPENROUTER_API_KEY') ?: '');

define('DB_HOST', getenv('DB_HOST') ?: '127.0.0.1');
define('DB_PORT', getenv('DB_PORT') ?: '3306');
define('DB_NAME', getenv('DB_NAME') ?: 'hayyak');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: '');

define('APP_URL', getenv('APP_URL') ?: 'http://localhost');
?>