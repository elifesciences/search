<?php

// Remove this if you want to suppress the warning.
require_once __DIR__.'/extra/gearman_shim.php';

return [
    'debug' => true,
    'validate' => true,
    'api_url' => 'http://0.0.0.0:1234',
    'elastic_servers' => ['http://elife_search_elasticsearch:9200'],
    'annotation_cache' => false,
    'ttl' => 0,
    'gearman_servers' => ['elife_gearman_1'],
    'gearman_auto_restart' => true,
    'aws' => [
        'mock_queue' => false,
        'queue_name' => 'eLife-search',
        'key' => '-----------------------',
        'secret' => '-------------------------------',
        'region' => '---------',
    ],
];
