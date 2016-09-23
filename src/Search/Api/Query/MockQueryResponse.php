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

    /**
     * Return the current element.
     *
     * @link http://php.net/manual/en/iterator.current.php
     *
     * @return mixed Can return any type
     *
     * @since 5.0.0
     */
    public function current()
    {
        return $this->items[$this->cursor];
    }

    /**
     * Move forward to next element.
     *
     * @link http://php.net/manual/en/iterator.next.php
     * @since 5.0.0
     */
    public function next()
    {
        ++$this->cursor;
    }

    /**
     * Return the key of the current element.
     *
     * @link http://php.net/manual/en/iterator.key.php
     *
     * @return mixed scalar on success, or null on failure
     *
     * @since 5.0.0
     */
    public function key()
    {
        return $this->cursor;
    }

    /**
     * Checks if current position is valid.
     *
     * @link http://php.net/manual/en/iterator.valid.php
     *
     * @return bool The return value will be casted to boolean and then evaluated.
     *              Returns true on success or false on failure
     *
     * @since 5.0.0
     */
    public function valid()
    {
        return isset($this->items[$this->cursor]);
    }

    /**
     * Rewind the Iterator to the first element.
     *
     * @link http://php.net/manual/en/iterator.rewind.php
     * @since 5.0.0
     */
    public function rewind()
    {
        $this->cursor = 0;
    }

    /**
     * String representation of object.
     *
     * @link http://php.net/manual/en/serializable.serialize.php
     *
     * @return string the string representation of the object or null
     *
     * @since 5.1.0
     */
    public function serialize()
    {
        return serialize($this->items);
    }

    /**
     * Constructs the object.
     *
     * @link http://php.net/manual/en/serializable.unserialize.php
     *
     * @param string $serialized <p>
     *                           The string representation of the object.
     *                           </p>
     *
     * @since 5.1.0
     */
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
        $type_totals['event'] = 0;
        $type_totals['interview'] = 0;
        $type_totals['labs-experiment'] = 0;
        $type_totals['podcast-episode'] = 0;

        return $type_totals;
    }

    public function getSubjects() : array
    {
        return [
            'biophysics-structural-biology' => 1,
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
