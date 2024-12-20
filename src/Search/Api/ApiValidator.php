<?php

namespace eLife\Search\Api;

use eLife\ApiValidator\MessageValidator\JsonMessageValidator;
use eLife\Search\Api\Response\SearchResponse;
use eLife\Search\Api\Response\TypesResponse;
use JMS\Serializer\SerializationContext;
use JMS\Serializer\Serializer;
use Symfony\Bridge\PsrHttpMessage\Factory\PsrHttpFactory;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/** @package eLife\Search\Api */
final class ApiValidator implements HasSearchResultValidator
{
    private Throwable|null $last_error;

    public function __construct(
        private Serializer $serializer,
        private SerializationContext $context,
        private JsonMessageValidator $validator,
        private PsrHttpFactory $bridge
    ) {
    }

    public function deserialize(string $item, string $classname): mixed
    {
        return $this->serializer->deserialize($item, $classname, 'json');
    }

    public function serialize(mixed $data): string
    {
        $context = clone $this->context;

        return $this->serializer->serialize($data, 'json', $context);
    }

    public function validateSearchResult(mixed $result, bool $strict = false) : bool
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

    private function validateSearchResponse(SearchResponse $data) : bool
    {
        $context = clone $this->context;
        $headers = [];
        $json = $this->serializer->serialize($data, 'json', $context);
        $headers = $data->getHeaders();

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

    public function getLastError(): Throwable|null
    {
        return $this->last_error;
    }
}
