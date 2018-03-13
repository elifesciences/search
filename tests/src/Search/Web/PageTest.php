<?php

namespace tests\eLife\Search\Web;

use Traversable;

final class PageTest extends ElasticTestCase
{
    /**
     * @test
     * @dataProvider invalidPageProvider
     */
    public function it_returns_a_400_for_an_invalid_page(string $page)
    {
        $this->newClient();
        $this->api->request('GET', "/search?page={$page}");
        $response = $this->getResponse();
        $this->assertEquals('application/problem+json', $response->headers->get('Content-Type'));
        $this->assertSame(400, $response->getStatusCode());
    }

    public function invalidPageProvider() : Traversable
    {
        foreach (['-1', '0', 'foo'] as $page) {
            yield 'page '.$page => [$page];
        }
    }

    /**
     * @test
     */
    public function it_returns_a_404_for_a_page_that_is_not_there()
    {
        $this->newClient();
        $this->api->request('GET', '/search?page=2');
        $response = $this->getResponse();
        $this->assertEquals('application/problem+json', $response->headers->get('Content-Type'));
        $this->assertSame(404, $response->getStatusCode());
    }

    /**
     * @test
     * @dataProvider invalidPerPageProvider
     */
    public function it_returns_a_400_for_an_invalid_per_page(string $perPage)
    {
        $this->newClient();
        $this->api->request('GET', "/search?per-page={$perPage}");
        $response = $this->getResponse();
        $this->assertEquals('application/problem+json', $response->headers->get('Content-Type'));
        $this->assertSame(400, $response->getStatusCode());
    }

    public function invalidPerPageProvider() : Traversable
    {
        foreach (['-1', '0', '101', 'foo'] as $page) {
            yield 'page '.$page => [$page];
        }
    }

    /**
     * @test
     */
    public function it_tags_high_numbered_pages_with_rate_limiting_headers()
    {
        $this->addDocumentsToElasticSearch([
            $this->getArticleFixture(0),
            $this->getArticleFixture(1),
            $this->getArticleFixture(2),
        ]);

        $this->newClient();

        $this->api->request('GET', '/search?page=1');
        $response = $this->getResponse();
        $this->assertNull($response->headers->get('X-Kong-Limit'));

        $this->api->request('GET', '/search?page=2');
        $response = $this->getResponse();
        $this->assertEquals('highpages=1', $response->headers->get('X-Kong-Limit'));
    }
}
