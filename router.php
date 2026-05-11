<?php
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Redirect bare root to Index.html
if ($uri === '/' || $uri === '') {
    header('Location: /Index.html');
    exit;
}

// Let PHP serve the file or directory if it actually exists
if (is_file(__DIR__ . $uri) || is_dir(__DIR__ . $uri)) {
    return false;
}

http_response_code(404);
echo '404 Not Found';
