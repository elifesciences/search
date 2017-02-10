<?php

namespace eLife\Search\Api\Query;

final class MockQueryResponse implements QueryResponse
{
    private $items;
    private $cursor;

    public function __construct(array $items)
    {
        $this->cursor = 0;
        $this->items = array_values($items);
    }

    public function current()
    {
        return $this->items[$this->cursor];
    }

    public function next()
    {
        ++$this->cursor;
    }

    public function key()
    {
        return $this->cursor;
    }

    public function valid()
    {
        return isset($this->items[$this->cursor]);
    }

    public function rewind()
    {
        $this->cursor = 0;
    }

    public function serialize()
    {
        return serialize($this->items);
    }

    public function unserialize($serialized)
    {
        $this->__construct(unserialize($serialized));
    }

    public function getTotalResults() : int
    {
        return count($this->items);
    }

    public function getTypeTotals() : array
    {
        $type_totals = [];
        $type_totals['correction'] = 0;
        $type_totals['editorial'] = 0;
        $type_totals['feature'] = 0;
        $type_totals['insight'] = 0;
        $type_totals['research-advance'] = 0;
        $type_totals['research-article'] = 0;
        $type_totals['research-exchange'] = 0;
        $type_totals['retraction'] = 0;
        $type_totals['registered-report'] = 0;
        $type_totals['replication-study'] = 0;
        $type_totals['short-report'] = 0;
        $type_totals['tools-resources'] = 0;
        $type_totals['blog-article'] = 0;
        $type_totals['collection'] = 0;
        $type_totals['interview'] = 0;
        $type_totals['labs-experiment'] = 0;
        $type_totals['podcast-episode'] = 0;

        return $type_totals;
    }

    public function getSubjects() : array
    {
        return [
            [
                'id' => 'biophysics-structural-biology',
                'name' => 'THIS IS MOCKED DATA.',
                'results' => 1,
            ],
        ];
    }

    public function toArray() : array
    {
        return $this->items;
    }

    public function map(callable $fn) : array
    {
        $response = [];
        foreach ($this->items as $item) {
            $response[] = $fn($item);
        }

        return $response;
    }
}
