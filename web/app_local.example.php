<?php

require_once __DIR__.'/bootstrap.php';

use eLife\Search\Kernel;

$kernel = new Kernel([
    'debug' => true,
    'validate' => true,
    'api_url' => 'http://0.0.0.0:1234',
    'elastic_url' => 'http://elife_search_elasticsearch:9200',
    'annotation_cache' => false,
    'ttl' => 0,
]);

$kernel->withApp(function ($app) {
    $app['debug'] = true;
});

$kernel->run();
