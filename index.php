<?php
// Thin front controller: route everything through public/index.php
// This keeps URLs at /Ironroot/ while using the app router and landing page.
define('BASE_DIR', __DIR__);
$public = __DIR__ . '/public/index.php';
if (file_exists($public)) {
    require $public;
} else {
    http_response_code(500);
    echo 'Public front controller not found.';
}