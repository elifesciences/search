<?php

namespace eLife\Search\Gearman;

class CompositeLimit
{
    public function __construct(callable ...$args)
    {
        $this->functions = $args;
    }

    public function __invoke()
    {
        return
            [] === array_filter($this->functions, function ($fn) {
                return $fn() === false;
            })
        ;
    }
}
