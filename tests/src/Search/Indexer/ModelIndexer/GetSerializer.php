<?php

namespace tests\eLife\Search\Indexer\ModelIndexer;

use eLife\ApiSdk\ApiSdk;
use Symfony\Component\Serializer\Serializer;
use tests\eLife\Search\HttpClient;

trait GetSerializer
{
    use HttpClient;

    private $serializer;

    public function getSerializer() : Serializer
    {
        if (null === $this->serializer) {
            $sdk = new ApiSdk($this->getHttpClient());
            $this->serializer = $sdk->getSerializer();
        }

        return $this->serializer;
    }
}
