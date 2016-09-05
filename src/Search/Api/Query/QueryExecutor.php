<?php

namespace eLife\Search\Api\Query;

interface QueryExecutor
{
    public function execute() : array;
}
