<?php

namespace eLife\Search;

interface MinimalKernel
{
    /**
     * @return MinimalKernel
     */
    public function withApp(callable $fn);

    public function run();
}
