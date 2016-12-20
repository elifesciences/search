<?php

namespace eLife\Search\Queue;

use Aws\Sqs\SqsClient;
use Throwable;

final class SqsWatchableQueue implements WatchableQueue
{
    private $client;
    private $url;
    private $next;

    public function __construct(SqsClient $client, string $name)
    {
        $this->client = $client;
        $this->url = $client->getQueueUrl(['QueueName' => $name])->get('QueueUrl');
    }

    /**
     * Adds item to the queue.
     *
     * Mock: Add item to queue.
     * SQS: This will set the queue item into the memory slot for re-processing.
     */
    public function enqueue(QueueItem $item) : bool
    {
        try {
            $this->client->sendMessage([
                'QueueUrl' => $this->url,
                'MessageBody' => json_encode([
                    'type' => $item->getType(),
                    'id' => $item->getId(),
                ]),
            ]);
        } catch (Throwable $e) {
            return false;
        }

        return true;
    }

    /**
     * Starts process of removing item.
     *
     * Mock: Move to separate "in progress" queue.
     * SQS: this will change the timeout of the in-memory item.
     */
    public function dequeue(int $timeoutOverride = null) : QueueItem
    {
        $next = $this->next;
        $this->next = null;

        return $next;
    }

    /**
     * Commits to removing item from queue, marks item as done and processed.
     *
     * Mock: Remove item completely.
     * SQS: this will delete the item from the queue.
     */
    public function commit(QueueItem $item)
    {
        $this->client->deleteMessage([
            'QueueUrl' => $this->url,
            'ReceiptHandle' => $item->getReceipt(),
        ]);
    }

    /**
     * This will happen when an error happens, we release the item back into the queue.
     *
     * Mock: re-add to queue.
     * SQS: this will set the queue item into the memory slot for re-processing. (Maybe delete item and re-add?)
     */
    public function release(QueueItem $item) : bool
    {
        try {
            $this->client->changeMessageVisibility([
                'QueueUrl' => $this->url,
                'ReceiptHandle' => $item->getReceipt(),
                'VisibilityTimeout' => 0,
            ]);
        } catch (Throwable $e) {
            return false;
        }

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
        if ($this->next !== null) {
            return true;
        }
        $message = $this->client->receiveMessage(['QueueUrl' => $this->url])->toArray();
        if (!SqsMessageTransformer::hasItems($message)) {
            return false;
        }
        $this->next = SqsMessageTransformer::fromMessage($message);

        return true;
    }

    public function clean()
    {
        $this->client->purgeQueue([
            'QueueUrl' => $this->url,
        ]);
    }
}
