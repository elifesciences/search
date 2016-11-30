<?php

namespace tests\eLife\Search\Workflow;

use eLife\ApiSdk\ApiSdk;
use Symfony\Component\Serializer\Serializer;
use tests\eLife\Search\HttpClient;

trait GetSerializer
{
    use HttpClient;

    private $serializer;

    public function getSerializer() : Serializer
    {
        if ($this->serializer === null) {
            $sdk = new ApiSdk($this->getHttpClient());
            $this->serializer = $sdk->getSerializer();
        }

        return $this->serializer;
    }
}
