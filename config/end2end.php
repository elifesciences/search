<?php

return [
    'gearman_servers' => ['127.0.0.1'],
    'elastic_servers' => ['http://end2end--search--1.elife.internal:9200'],
    'api_url' => 'http://end2end--gateway.elife.internal/',
    'api_requests_batch' => 20,
    'elastic_logging' => true,
    'rate_limit_minimum_page' => 21,
    'aws' => [
        'queue_name' => 'search--end2end',
        'credential_file' => true,
        'region' => 'us-east-1',
    ],
];
