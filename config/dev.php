<?php

require_once __DIR__.'/extra/gearman_shim.php';

return [
    'debug' => true,
    'validate' => true,
    'api_url' => 'http://localhost:8080',
    'ttl' => 0,
    'gearman_servers' => ['localhost'],
    'gearman_auto_restart' => false,
];
