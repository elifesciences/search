<?php

require_once __DIR__.'/extra/gearman_shim.php';

return [
    'debug' => true,
    'validate' => true,
    'api_url' => 'http://localhost:8080',
    'ttl' => 0,
    'gearman_servers' => ['127.0.0.1'],
    'gearman_auto_restart' => false,
    'elastic_force_sync' => true,
    'elastic_logging' => true,
    'aws' => [
        'queue_name' => 'search--dev',
        'credential_file' => true,
        'region' => 'us-east-1',
        'endpoint' => 'http://localhost:4100',
    ],
    'feature_rds' => false,
    'rds_articles' => [
        '30274' => [
            'date' => '2020-01-29T19:18:00Z',
            'display' => 'https://hub.stenci.la/elife/30274/main/?elife',
            'download' => 'https://hub.stenci.la/elife/30274/files/download/article-30274.md',
        ],
    ],
];
