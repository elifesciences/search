<?php

namespace eLife\Search\Queue;

use eLife\ApiSdk\ApiSdk;
use LogicException;

final class SqsMessageTransformer implements QueueItemTransformer
{
    private $sdk;
    private $serializer;

    public function __construct(
        ApiSdk $sdk
    ) {
        $this->serializer = $sdk->getSerializer();
        $this->sdk = $sdk;
    }

    public static function fromMessage($message) : QueueItem
    {
        $messageId = $message['MessageId'];
        $body = json_decode($message['Body']);
        $md5 = $message['MD5OfBody'];
        $handle = $message['ReceiptHandle'];
        if (md5($message['Body']) !== $md5) {
            // Do something...
        }

        return new SqsMessage($messageId, $body->id ?? $body->number, $body->type, $handle);
    }

    public static function hasItems(array $message)
    {
        // If Messages exists and is not empty.
        return isset($message['Messages']) ? (empty($message['Messages']) ? false : true) : false;
    }

    public function getSdk(QueueItem $item)
    {
        switch ($item->getType()) {
            case 'blog-article':
                return $this->sdk->blogArticles();
                break;

            // ...
            default:
                throw new LogicException('Wat');
        }
    }

    public function transform(QueueItem $item)
    {
        $sdk = $this->getSdk($item);
        $entity = $sdk->get($item->getId());

        return $this->serializer->serialize($entity->wait(true), 'json');
    }

    public function getGearmanTask(QueueItem $item) : string
    {
        switch ($item->getType()) {
            case 'blog-article':
                return 'blog_article_validate';

            // ...
            default:
                throw new LogicException('Wat');
        }
    }
}
