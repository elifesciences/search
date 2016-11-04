<?php

namespace eLife\Search\Queue\Mock;

use eLife\Search\Queue\QueueItem;

final class QueueItemMock implements QueueItem
{
    private $type;
    private $id;

    public function __construct(string $type, string $id)
    {
        $this->type = $type;
        $this->id = $id;
    }

    /**
     * Id or Number.
     */
    public function getId() : string
    {
        return $this->id;
    }

    /**
     * Type (Article, Collection, Event etc.).
     */
    public function getType() : string
    {
        return $this->type;
    }

    /**
     * SQS ReceiptHandle.
     */
    public function getReceipt() : string
    {
        return $this->type.'--'.$this->id;
    }
}
