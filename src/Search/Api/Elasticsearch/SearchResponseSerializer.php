<?php

namespace eLife\Search\Api\Elasticsearch;

use Elasticsearch\Serializers\SerializerInterface;
use eLife\Search\Api\Response\SearchResult;
use JMS\Serializer\Serializer;

final class SearchResponseSerializer implements SerializerInterface
{
    private $serializer;

    public function __construct(Serializer $serializer)
    {
        $this->serializer = $serializer;
    }

    /**
     * Serialize a complex data-structure into a json encoded string.
     *
     * @param mixed   The data to encode
     *
     * @return string
     */
    public function serialize($data) : string
    {
        $searchResult = $this->serializer->serialize($data, 'json');

        return $searchResult;
    }

    /**
     * Deserialize json encoded string into an associative array.
     *
     * @param string $data    JSON encoded string
     * @param array  $headers Response Headers
     *
     * @return array
     */
    public function deserialize($json, $headers)
    {
        return $this->serializer->deserialize($json, SearchResult::class, 'json');
    }
}
