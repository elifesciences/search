<?php

namespace eLife\Search\Queue;

interface QueueItemTransformer
{
    public function transform(QueueItem $item) : object;

    public function getGearmanTask(QueueItem $item) : string;
}
