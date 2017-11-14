<?php

require_once __DIR__.'/../vendor/autoload.php';
umask(0002);
$defaultFile = __DIR__.'/../config.php';
$environmentSpecificFile = __DIR__.'/../config/'.getenv('ENVIRONMENT_NAME').'.php';
if (file_exists($defaultFile)) {
    $config = include $defaultFile;
} else {
    $config = [];
}

if (file_exists($environmentSpecificFile)) {
    // temporary fallback to environment-based file
    $config = array_merge($config, include $environmentSpecificFile);
}
