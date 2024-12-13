<?php

namespace tests\eLife\Search\Web;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Traversable;

final class TypeTest extends ElasticTestCase
{
    #[Test]
    #[DataProvider('typeProvider')]
    public function itNegotiatesType(string $type, int $statusCode)
    {
        $response = $this->performRequest($type);
        $this->assertSame($statusCode, $response->getStatusCode());
    }

    public static function typeProvider(): Traversable
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

    #[Test]
    #[DataProvider('contentTypeProvider')]
    public function testContentType(string $type, string $contentType)
    {
        $response = $this->performRequest($type);
        $this->assertSame($contentType, $response->headers->get('Content-Type'));
    }

    public static function contentTypeProvider(): Traversable
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
