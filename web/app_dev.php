<?php

require_once __DIR__.'/../bootstrap.php';

use eLife\Search\Kernel;

$kernel = new Kernel($config);

$kernel->withApp(function ($app) use ($config) {
    $app['debug'] = $config['debug'] ?? false;
});

$kernel->run();
