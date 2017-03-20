<?php

namespace tests\eLife\Search\Web;

/**
 * @group failing
 */
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

        $this->jsonRequest('GET', '/search', [], ['If-None-Match' => $eTag]);
        $response = $this->getResponse();
        $this->assertEquals(304, $response->getStatusCode());

        $this->jsonRequest('GET', '/search', [], ['If-None-Match' => 'NOT REAL ETAG']);
        $response = $this->getResponse();
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function modifyConfiguration($config)
    {
        $config['ttl'] = 300;

        return $config;
    }
}
