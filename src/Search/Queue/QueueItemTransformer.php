<?php

namespace eLife\Search\Queue;

interface QueueItemTransformer
{
    public function transform(QueueItem $item);

    public function getGearmanTask(QueueItem $item) : string;
}
