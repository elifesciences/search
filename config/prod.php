<?php

use Monolog\Logger;

return [
    'gearman_servers' => ['localhost'],
    'api_url' => 'http://prod--gateway.elife.internal/',
    'api_requests_batch' => 20,
    'aws' => [
        'queue_name' => 'search--prod',
        'credential_file' => true,
        'region' => 'us-east-1',
    ],
    'logging_level' => Logger::INFO,
];
