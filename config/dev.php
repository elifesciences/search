<?php

require_once __DIR__.'/extra/gearman_shim.php';

return [
    'debug' => true,
    'validate' => true,
    'api_url' => 'http://localhost:8080',
    'ttl' => 0,
    'gearman_servers' => ['localhost'],
    'gearman_auto_restart' => false,
    'elastic_force_sync' => true,
    'aws' => [
        'queue_name' => 'search--dev',
        'credential_file' => true,
        'region' => 'us-east-1',
        'endpoint' => 'http://localhost:4100'
    ],
];
