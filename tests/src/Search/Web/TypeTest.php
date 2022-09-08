<?php

namespace tests\eLife\Search\Web;

use Traversable;

final class TypeTest extends ElasticTestCase
{
    /**
     * @test
     * @dataProvider typeProvider
     */
    public function itNegotiatesType(string $type, int $statusCode)
    {
        $this->newClient();

        $this->api->request('GET', '/search', [], [], ['HTTP_ACCEPT' => $type]);
        $response = $this->api->getResponse();
        $this->assertSame($statusCode, $response->getStatusCode());
    }

    public function typeProvider() : Traversable
    {
        $types = [
            'application/vnd.elife.search+json' => 200,
            'application/vnd.elife.search+json; version=0' => 406,
            'application/vnd.elife.search+json; version=1' => 200,
            'application/vnd.elife.search+json; version=2' => 200,
            'text/plain' => 406,
        ];

        foreach ($types as $type => $statusCode) {
            yield $type => [$type, $statusCode];
        }
    }
}
