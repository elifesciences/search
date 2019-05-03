<?php

namespace tests\eLife\Search;

use Csa\GuzzleHttp\Middleware\Cache\Adapter\StorageAdapterInterface;
use eLife\ApiValidator\Exception\InvalidMessage;
use eLife\ApiValidator\MessageValidator;
use function GuzzleHttp\json_decode;
use function GuzzleHttp\json_encode;
use Psr\Http\Message\MessageInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use RuntimeException;

final class ValidatingStorageAdapter implements StorageAdapterInterface
{
    private $storageAdapter;
    private $validator;

    public function __construct(StorageAdapterInterface $storageAdapter, MessageValidator $validator)
    {
        $this->storageAdapter = $storageAdapter;
        $this->validator = $validator;
    }

    public function fetch(RequestInterface $request)
    {
        return $this->storageAdapter->fetch($request);
    }

    public function save(RequestInterface $request, ResponseInterface $response)
    {
        try {
            $this->validator->validate($request);
        } catch (InvalidMessage $e) {
            throw new RuntimeException('Request JSON schema validation failed: '.$this->dumpJsonBody($request), -1, $e);
        }
        try {
            $this->validator->validate($response);
        } catch (InvalidMessage $e) {
            throw new RuntimeException('Response JSON schema validation failed: '.$this->dumpJsonBody($response), -1, $e);
        }

        $this->storageAdapter->save($request, $response);
    }

    private function dumpJsonBody(MessageInterface $message) : string
    {
        return json_encode(json_decode($message->getBody(), true), JSON_PRETTY_PRINT);
    }
}
