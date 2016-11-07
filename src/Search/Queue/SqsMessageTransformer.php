<?php

namespace eLife\Search\Queue;

use eLife\ApiSdk\ApiSdk;

final class SqsMessageTransformer implements QueueItemTransformer
{
    use BasicTransformer;

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
        $message = array_shift($message['Messages']);
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
}
