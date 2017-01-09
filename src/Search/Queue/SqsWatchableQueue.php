<?php

namespace eLife\Search\Queue;

use Aws\Sqs\SqsClient;
use Throwable;

final class SqsWatchableQueue implements WatchableQueue
{
    private $client;
    private $url;
    private $pollingTimeout = 20;
    private $visibilityTimeout = 10;

    public function __construct(SqsClient $client, string $name)
    {
        $this->client = $client;
        $this->url = $client->getQueueUrl(['QueueName' => $name])->get('QueueUrl');
    }

    /**
     * Adds item to the queue.
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
     * Get an item from the queue and start is processing,
     * making it invisible to other processes for $timeoutOverride seconds.
     */
    public function dequeue()
    {
        $message = $this->client->receiveMessage([
            'QueueUrl' => $this->url,
            'WaitTimeSeconds' => $this->pollingTimeout,
            'VisibilityTimeout' => $this->visibilityTimeout,
        ])->toArray();

        if (!SqsMessageTransformer::hasItems($message)) {
            return false;
        }

        return SqsMessageTransformer::fromMessage($message);
    }

    /**
     * Commits to removing item from queue, marks item as done and processed.
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

    public function clean()
    {
        $this->client->purgeQueue([
            'QueueUrl' => $this->url,
        ]);
    }

    public function count() : int
    {
        $attributes = [
            'ApproximateNumberOfMessages',
            'ApproximateNumberOfMessagesDelayed',
            'ApproximateNumberOfMessagesNotVisible',
        ];
        $result = $this->client->getQueueAttributes([
            'AttributeNames' => $attributes,
            'QueueUrl' => $this->url,
        ]);
        $total = 0;
        foreach ($attributes as $attributeName) {
            $total += $result['Attributes'][$attributeName];
        }

        return $total;
    }
}
