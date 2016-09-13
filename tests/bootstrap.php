<?php

require_once __DIR__.'/../vendor/autoload.php';

if (!class_exists('GearmanClient')) {
    class GearmanClient
    {
    }

    define('GEARMAN_SUCCESS', 'GEARMAN_SUCCESS');
}
