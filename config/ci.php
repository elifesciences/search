<?php

require_once __DIR__.'/extra/gearman_shim.php';

return [
    'debug' => false,
    'validate' => true,
    'ttl' => 0,
    'gearman_servers' => ['localhost'],
    'gearman_auto_restart' => false,
];
