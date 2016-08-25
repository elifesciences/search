<?php

require_once '../vendor/autoload.php';

use eLife\Search\Kernel;

$app = Kernel::create([
    'debug' => true,
    'validate' => true,
]);
$app->run();
