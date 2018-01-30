<?php

use Monolog\Logger;

return [
    'gearman_servers' => ['prod--search--1.elife.internal'],
    'elastic_servers' => ['http://prod--search--1.elife.internal:9200'],
    'api_url' => 'http://prod--gateway.elife.internal/',
    'api_requests_batch' => 20,
    'aws' => [
        'queue_name' => 'search--prod',
        'credential_file' => true,
        'region' => 'us-east-1',
    ],
    'logger.level' => Logger::INFO,
];
