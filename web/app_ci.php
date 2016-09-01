<?php

require_once __DIR__.'/bootstrap.php';

use eLife\Search\Kernel;

$kernel = new Kernel([
    'debug' => true,
    'validate' => true,
    'ttl' => 0,
]);

$kernel->withApp(function($app) {
    $app['debug'] = true;
});

$kernel->run();
