<?php

use ShipMonk\ComposerDependencyAnalyser\Config\Configuration;
use ShipMonk\ComposerDependencyAnalyser\Config\ErrorType;

$config = new Configuration();

return $config
    ->ignoreErrorsOnPackage('elife/api', [ErrorType::UNUSED_DEPENDENCY])
    ->addPathToScan(__DIR__ . '/bin/console', isDev: false)
;
