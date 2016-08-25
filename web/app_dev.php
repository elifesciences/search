<?php

require_once __DIR__.'/bootstrap.php';

use eLife\Search\Kernel;

$app = Kernel::create([
    'debug' => true,
    'validate' => true,
    'ttl' => 0,
]);
$app['debug'] = true;

$app->run();
