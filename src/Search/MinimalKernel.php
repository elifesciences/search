<?php

namespace eLife\Search;

use Silex\Application;

interface MinimalKernel
{

    public function withApp(callable $fn);

    public function run();

}
