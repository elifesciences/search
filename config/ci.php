<?php

require_once __DIR__.'/extra/gearman_shim.php';

return [
    'debug' => false,
    'validate' => true,
    'api_url' => 'http://localhost:8080',
    'ttl' => 0,
    'elastic_force_sync' => true,
    'gearman_servers' => ['localhost'],
    'gearman_auto_restart' => false,
    'aws' => [
        'queue_name' => 'search--ci',
        'credential_file' => true,
        'region' => 'us-east-1',
        'endpoint' => 'http://localhost:4100',
    ],
];
