<?php

require_once __DIR__.'/extra/gearman_shim.php';

return [
    'validate' => true,
    'api_url' => 'http://localhost:8080',
    'ttl' => 0,
    'elastic_force_sync' => true,
    'gearman_servers' => ['127.0.0.1'],
    'elastic_logging' => true,
    'gearman_auto_restart' => false,
    'aws' => [
        'queue_name' => 'search--ci',
        'credential_file' => true,
        'region' => 'us-east-1',
        'endpoint' => 'http://localhost:4100',
    ],
];
