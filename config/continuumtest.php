<?php

return [
    'debug' => true,
    'gearman_servers' => ['localhost'],
    'api_url' => 'http://continuumtest--gateway.elifesciences.org/',
    'api_requests_batch' => 20,
    'aws' => [
        'queue_name' => 'search--continuumtest',
        'credential_file' => true,
        'region' => 'us-east-1',
    ],
];
