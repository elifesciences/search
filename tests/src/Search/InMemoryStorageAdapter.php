<?php

namespace tests\eLife\Search;

use Csa\Bundle\GuzzleBundle\Cache\StorageAdapterInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

final class InMemoryStorageAdapter implements StorageAdapterInterface
{
    private $array = [];
    private $requestHeadersBlacklist = [
        'User-Agent',
        'Host',
        'X-Guzzle-Cache',
    ];
    private $responseHeadersBlacklist = [
        'X-Guzzle-Cache',
    ];

    public function __construct(array $requestHeadersBlacklist = [], array $responseHeadersBlacklist = [])
    {
        if (!empty($requestHeadersBlacklist)) {
            $this->requestHeadersBlacklist = $requestHeadersBlacklist;
        }
        if (!empty($responseHeadersBlacklist)) {
            $this->responseHeadersBlacklist = $responseHeadersBlacklist;
        }
    }

    public function fetch(RequestInterface $request)
    {
        $key = $this->getKey($request);

        if (empty($this->array[$key])) {
            return null;
        }

        return $this->array[$key];
    }

    public function save(RequestInterface $request, ResponseInterface $response)
    {
        $key = $this->getKey($request);

        foreach ($this->responseHeadersBlacklist as $header) {
            $response = $response->withoutHeader($header);
        }

        $this->array[$key] = $response;
    }

    private function getKey(RequestInterface $request)
    {
        return md5(serialize([
            'method' => $request->getMethod(),
            'path' => $request->getUri()->getPath(),
            'query' => $request->getUri()->getQuery(),
            'user_info' => $request->getUri()->getUserInfo(),
            'port' => $request->getUri()->getPort(),
            'scheme' => $request->getUri()->getScheme(),
            'headers' => array_diff_key($request->getHeaders(), array_flip($this->requestHeadersBlacklist)),
        ]));
    }
}
