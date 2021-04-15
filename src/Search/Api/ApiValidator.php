<?php

namespace eLife\Search\Api;

use eLife\ApiValidator\MessageValidator\JsonMessageValidator;
use eLife\Search\Api\Response\SearchResponse;
use eLife\Search\Api\Response\SearchResult;
use eLife\Search\Api\Response\TypesResponse;
use JMS\Serializer\SerializationContext;
use JMS\Serializer\Serializer;
use Symfony\Bridge\PsrHttpMessage\Factory\DiactorosFactory;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

final class ApiValidator
{
    private $serializer;
    private $context;
    private $last_error;

    public function __construct(
        Serializer $serializer,
        SerializationContext $context,
        JsonMessageValidator $validator,
        DiactorosFactory $bridge
    ) {
        $this->validator = $validator;
        $this->bridge = $bridge;
        $this->serializer = $serializer;
        $this->context = $context;
    }

    public function deserialize($item, $classname)
    {
        return $this->serializer->deserialize($item, $classname, 'json');
    }

    public function serialize($data)
    {
        $context = clone $this->context;

        return $this->serializer->serialize($data, 'json', $context);
    }

    public function validateSearchResult($result, $strict = false) : bool
    {
        $searchResponse = new SearchResponse([$result], 1, [
            [
                'id' => 'biophysics-structural-biology',
                'name' => 'Biophysics Structural Biology',
                'results' => 1,
            ],
        ], TypesResponse::fromArray([]));

        $isValid = $this->validateSearchResponse($searchResponse);
        if ($strict && !$isValid) {
            throw $this->getLastError();
        }

        return $isValid;
    }

    public function validateSearchResponse($data, int $version = null, $group = null) : bool
    {
        $context = clone $this->context;
        if ($version) {
            $context->setVersion($version);
        }
        if ($group) {
            $context->setGroups([$group]);
        }
        $headers = [];
        $json = $this->serializer->serialize($data, 'json', $context);
        if ($data instanceof HasHeaders) {
            $headers = $data->getHeaders();
        }

        return $this->validate(new Response($json, 200, $headers));
    }

    public function validate(Response $response) : bool
    {
        $this->last_error = null;
        $pass = true;
        try {
            $this->validator->validate(
                $this->bridge->createResponse($response)
            );
        } catch (Throwable $e) {
            $this->last_error = $e;
            $pass = false;
        }

        return $pass;
    }

    public function getLastError()
    {
        return $this->last_error;
    }
}
