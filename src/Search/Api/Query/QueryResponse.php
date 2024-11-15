<?php

namespace eLife\Search\Api\Query;

use Iterator;

interface QueryResponse extends Iterator
{
    public function getTotalResults() : int;

    public function getTypeTotals() : array;

    public function getSubjects() : array;

    public function toArray() : array;

    public function map(callable $fn) : array;
}
