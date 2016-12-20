<?php

namespace eLife\Search\Queue;

interface WatchableQueue
{
    /**
     * Adds item to the queue.
     *
     * Mock: Add item to queue.
     * SQS: This will set the queue item into the memory slot for re-processing.
     */
    public function enqueue(QueueItem $item) : bool;

    /**
     * Starts process of removing item.
     *
     * Mock: Move to separate "in progress" queue.
     * SQS: this will change the timeout of the in-memory item.
     */
    public function dequeue(int $timeoutOverride = null) : QueueItem;

    /**
     * Commits to removing item from queue, marks item as done and processed.
     *
     * Mock: Remove item completely.
     * SQS: this will delete the item from the queue.
     */
    public function commit(QueueItem $item);

    /**
     * This will happen when an error happens, we release the item back into the queue.
     *
     * Mock: re-add to queue.
     * SQS: this will set the queue item into the memory slot for re-processing. (Maybe delete item and re-add?)
     */
    public function release(QueueItem $item) : bool;

    /**
     * Returns false if queue is empty.
     *
     * Mock: isEmpty check.
     * SQS: this will take an item off the queue and store it in memory unless there is one already stored in memory.
     */
    public function isValid() : bool;

    /**
     * Deletes everything from the queue.
     */
    public function clean();
}
