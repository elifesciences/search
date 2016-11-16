<?php

return [
    'gearman_servers' => ['localhost'],
    'api_url' => 'http://end2end--gateway.elifesciences.org/',
    'aws' => [
        'queue_name' => 'search--end2end',
        'credential_file' => true,
        'region' => 'us-east-1',
    ],
];
