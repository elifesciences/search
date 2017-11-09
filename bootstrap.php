<?php

require_once __DIR__.'/vendor/autoload.php';
umask(0002);
$defaultFile = __DIR__.'/config/config.php';
$legacyFile = __DIR__.'/config/'.getenv('ENVIRONMENT_NAME').'.php';
if (file_exists($defaultFile)) {
    $config = include $defaultFile;
} else if (file_exists($legacyFile)) {
    // temporary fallback to environment-based file
    $config = include $legacyFile;
} else {
    throw new RuntimeException("Can't find a config/config.php file");
}
