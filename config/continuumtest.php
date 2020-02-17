<?php

return [
    'gearman_servers' => ['127.0.0.1'],
    'api_url' => 'http://continuumtest--gateway.elife.internal/',
    'api_requests_batch' => 20,
    'rate_limit_minimum_page' => 21,
    'aws' => [
        'queue_name' => 'search--continuumtest',
        'credential_file' => true,
        'region' => 'us-east-1',
    ],
];
