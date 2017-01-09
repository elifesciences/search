<?php

namespace eLife\Search\Limit;

interface Limit
{
    public function __invoke() : bool;
    public function getReasons() : array;
}
