<?php

namespace eLife\Search;

interface MinimalKernel
{
    public function withApp(callable $fn);

    public function run();
}
