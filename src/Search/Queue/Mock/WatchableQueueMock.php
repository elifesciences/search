<?php

namespace eLife\Search\Queue\Mock;

use eLife\Search\Queue\QueueItem;
use eLife\Search\Queue\WatchableQueue;

final class WatchableQueueMock implements WatchableQueue
{
    public $items = [];
    private $process = [];

    public function __construct(QueueItem ...$items)
    {
        $this->items = $items;
    }

    /**
     * Adds item to the queue.
     *
     * Mock: Add item to queue.
     * SQS: This will set the queue item into the memory slot for re-processing.
     */
    public function enqueue(QueueItem $item) : bool
    {
        array_push($this->items, $item);

        return true;
    }

    /**
     * Starts process of removing item.
     *
     * Mock: Move to separate "in progress" queue.
     * SQS: this will change the timeout of the in-memory item.
     */
    public function dequeue(int $timeout = 30) : QueueItem
    {
        $item = array_pop($this->items);

        return $this->process[$item->getReceipt()] = $item;
    }

    /**
     * Commits to removing item from queue, marks item as done and processed.
     *
     * Mock: Remove item completely.
     * SQS: this will delete the item from the queue.
     */
    public function commit(QueueItem $item) : bool
    {
        $value = (isset($this->process[$item->getReceipt()]));
        if ($value) {
            unset($this->process[$item->getReceipt()]);
        }

        return $value;
    }

    /**
     * This will happen when an error happens, we release the item back into the queue.
     *
     * Mock: re-add to queue.
     * SQS: this will set the queue item into the memory slot for re-processing. (Maybe delete item and re-add?)
     */
    public function release(QueueItem $item) : bool
    {
        array_unshift($this->items, $item);

        return true;
    }

    /**
     * Returns false if queue is empty.
     *
     * Mock: isEmpty check.
     * SQS: this will take an item off the queue and store it in memory unless there is one already stored in memory.
     */
    public function isValid() : bool
    {
        return !empty($this->items);
    }
}
