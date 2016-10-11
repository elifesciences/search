<?php

require_once __DIR__.'/extra/gearman-shim.php';

return [
    'debug' => true,
    'validate' => true,
    'ttl' => 0,
    'gearman_servers' => ['localhost'],
];
