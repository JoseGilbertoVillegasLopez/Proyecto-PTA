<?php

if (PHP_SAPI === 'cli-server') {
    $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '/';
    $file = __DIR__ . '/public' . $path;

    if (is_file($file)) {
        return false;
    }
}

require __DIR__ . '/public/index.php';
