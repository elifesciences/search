<?php

namespace tests\eLife\Search\Web;

use Traversable;

final class PageTest extends ElasticTestCase
{
    /**
     * @test
     * @dataProvider invalidPageProvider
     */
    public function it_returns_a_404_for_an_invalid_page(string $page)
    {
        $this->newClient();
        $this->api->request('GET', "/search?page={$page}");
        $response = $this->getResponse();
        $this->assertEquals('application/problem+json', $response->headers->get('Content-Type'));
        // TODO $this->assertSame(404, $response->getStatusCode());
    }

    public function invalidPageProvider() : Traversable
    {
        foreach (['-1', '0', '2'/*, TODO 'foo'*/] as $page) {
            yield 'page '.$page => [$page];
        }
    }
}
