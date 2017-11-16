<?php

require_once __DIR__.'/../src/bootstrap.php';

(new eLife\Search\Kernel($config))->run();
