<?php

namespace tests\eLife\Search\Web;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Traversable;

#[Group('web')]
final class PageTest extends ElasticTestCase
{
    #[Test]
    #[DataProvider('invalidPageProvider')]
    public function itReturnsA400ForAnInvalidPage(string $page)
    {
        $this->newClient();
        $this->api->request('GET', "/search?page={$page}");
        $response = $this->getResponse();
        $this->assertEquals('application/problem+json', $response->headers->get('Content-Type'));
        $this->assertSame(400, $response->getStatusCode());
    }

    public static function invalidPageProvider() : Traversable
    {
        foreach (['-1', '0', 'foo'] as $page) {
            yield 'page '.$page => [$page];
        }
    }

    #[Test]
    public function itReturnsA404ForAPageThatIsNotThere()
    {
        $this->newClient();
        $this->api->request('GET', '/search?page=2');
        $response = $this->getResponse();
        $this->assertEquals('application/problem+json', $response->headers->get('Content-Type'));
        $this->assertSame(404, $response->getStatusCode());
    }

    #[Test]
    public function itReturnsA400WhenRequestedPageSizeExceedsLimit()
    {
        $this->newClient();
        $this->api->request('GET', '/search?per-page=10&page=1001');
        $response = $this->getResponse();
        $this->assertEquals('application/problem+json', $response->headers->get('Content-Type'));
        $this->assertSame(400, $response->getStatusCode());
    }

    #[Test]
    #[DataProvider('invalidPerPageProvider')]
    public function itReturnsA400ForAnInvalidPerPage(string $perPage)
    {
        $this->newClient();
        $this->api->request('GET', "/search?per-page={$perPage}");
        $response = $this->getResponse();
        $this->assertEquals('application/problem+json', $response->headers->get('Content-Type'));
        $this->assertSame(400, $response->getStatusCode());
    }

    public static function invalidPerPageProvider() : Traversable
    {
        foreach (['-1', '0', '101', 'foo'] as $page) {
            yield 'page '.$page => [$page];
        }
    }

    #[Test]
    public function itTagsHighNumberedPagesWithRateLimitingHeaders()
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
