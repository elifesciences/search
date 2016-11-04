<?php

namespace eLife\Search\Queue\Mock;

use eLife\ApiSdk\ApiSdk;
use eLife\Search\Queue\BasicTransformer;
use eLife\Search\Queue\QueueItemTransformer;

final class QueueItemTransformerMock implements QueueItemTransformer
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
}
