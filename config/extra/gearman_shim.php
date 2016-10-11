<?php

if (!class_exists('GearmanClient')) {
    class GearmanClient
    {
        public function addServer($server)
        {
        }
    }
    class GearmanWorker
    {
        public function addServer($server)
        {
        }
    }

    define('GEARMAN_SUCCESS', 'GEARMAN_SUCCESS');
    define('GEARMAN_INSTALLED', false);

    echo "\e[31m========================================================\n\e[0m";
    echo "\e[31mWARNING: Gearman is not installed, no jobs will be run. \n\e[0m";
    echo "\e[31m========================================================\n\e[0m";
}
