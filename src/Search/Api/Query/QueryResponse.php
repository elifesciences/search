<?php

namespace eLife\Search\Api\Query;

use Iterator;

/**
 * @template TKey
 * @template TValue
 * @extends Iterator<TKey, TValue>
 */
interface QueryResponse extends Iterator
{
    public function getTotalResults() : int;

    /** @return array<string,int> */
    public function getTypeTotals() : array;

    /** @return array<array{id: string, results: int}> */
    public function getSubjects() : array;

    /** @return array<mixed> */
    public function toArray() : array;

    /** @return array<mixed> */
    public function map(callable $fn) : array;
}
