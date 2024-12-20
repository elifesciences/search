<?php

namespace eLife\Search\Api\Elasticsearch;

use Elasticsearch\Serializers\SerializerInterface;
use eLife\Search\Api\Elasticsearch\Response\ElasticResponse;
use eLife\Search\Api\Elasticsearch\Response\ErrorResponse;
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
     * @param mixed $data The data to encode
     */
    public function serialize($data) : string
    {
        if (is_string($data)) {
            return $data;
        }
        $searchResult = $this->serializer->serialize($data, 'json');

        return $searchResult;
    }

    /**
     * Deserialize json encoded string into an associative array.
     *
     * @param string $json    JSON encoded string
     * @param array  $headers Response Headers
     *
     * @return array
     */
    public function deserialize($json, $headers)
    {
        $response = $this->serializer->deserialize($json, ElasticResponse::class, 'json');
        // This had to be added because of ES's "array-only" error handling.
        $return = [
            'payload' => $response,
        ];
        if ($response instanceof ErrorResponse) {
            $return['error'] = $response->error;
        }

        return $return;
    }
}
