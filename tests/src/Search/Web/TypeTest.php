<?php

namespace tests\eLife\Search\Web;

use Symfony\Component\HttpFoundation\Response;
use Traversable;

final class TypeTest extends ElasticTestCase
{
    /**
     * @test
     * @dataProvider typeProvider
     */
    public function itNegotiatesType(string $type, int $statusCode, string $contentType = null)
    {
        $response = $this->performRequest($type);
        $this->assertSame($statusCode, $response->getStatusCode());
    }

    public function typeProvider(): Traversable
    {
        $types = [
            'application/vnd.elife.search+json' => 200,
            'application/vnd.elife.search+json; version=0' => 406,
            'application/vnd.elife.search+json; version=1' => 200,
            'application/vnd.elife.search+json; version=2' => 200,
            'application/vnd.elife.search+json; version=3' => 406,
            'text/plain' => 406,
        ];

        foreach ($types as $type => $statusCode) {
            yield $type => [$type, $statusCode];
        }
    }

    /**
     * @test
     * @dataProvider contentTypeProvider
     */
    public function testContentType($type, $contentType)
    {
        $response = $this->performRequest($type);
        $this->assertSame($contentType, $response->headers->get('Content-Type'));
    }

    public function contentTypeProvider()
    {
        $types = [
            'application/vnd.elife.search+json' => 'application/vnd.elife.search+json; version=2',
            'application/vnd.elife.search+json; version=1' => 'application/vnd.elife.search+json; version=1',
            'application/vnd.elife.search+json; version=2' => 'application/vnd.elife.search+json; version=2',
        ];

        foreach ($types as $type => $expected) {
            yield $type => [$type, $expected];
        }
    }

    private function performRequest(string $type)
    {
        $this->newClient();

        $this->api->request('GET', '/search', [], [], ['HTTP_ACCEPT' => $type]);
        return $this->api->getResponse();
    }
}
