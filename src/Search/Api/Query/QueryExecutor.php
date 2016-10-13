<?php

namespace eLife\Search\Api\Query;

interface QueryExecutor
{
    public function execute() : QueryResponse;

    public function getHash() : string;
}
