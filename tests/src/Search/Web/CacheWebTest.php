<?php

namespace tests\eLife\Search\Web;

class CacheWebTest extends ElasticTestCase
{
    public function testETag()
    {
        $this->addDocumentsToElasticSearch([
            $this->getArticleFixture(0),
            $this->getArticleFixture(1),
        ]);
        $this->newClient();
        $this->jsonRequest('GET', '/search');
        $response = $this->getResponse();
        $this->assertEquals(200, $response->getStatusCode());
        $eTag = $response->headers->get('ETag');
        $this->assertEquals('max-age=300, public, stale-if-error=86400, stale-while-revalidate=300', $response->headers->get('Cache-Control'));
        $this->assertEquals('Accept', $response->headers->get('Vary'));

        $this->jsonRequest('GET', '/search', [], ['If-None-Match' => $eTag]);
        $response = $this->getResponse();
        $this->assertEquals(304, $response->getStatusCode());
        $this->assertEquals('max-age=300, public, stale-if-error=86400, stale-while-revalidate=300', $response->headers->get('Cache-Control'));
        $this->assertEquals('Accept', $response->headers->get('Vary'));

        $this->jsonRequest('GET', '/search', [], ['If-None-Match' => 'NOT REAL ETAG']);
        $response = $this->getResponse();
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('max-age=300, public, stale-if-error=86400, stale-while-revalidate=300', $response->headers->get('Cache-Control'));
        $this->assertEquals('Accept', $response->headers->get('Vary'));
    }

    public function modifyConfiguration($config)
    {
        $config['ttl'] = 300;

        return $config;
    }
}
