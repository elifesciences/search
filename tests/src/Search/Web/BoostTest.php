<?php

namespace tests\eLife\Search\Web;

/**
 * @group web
 */
class BoostTest extends ElasticTestCase
{
    /**
     * @test
     */
    public function test_boost_by_type()
    {
        $this->addDocumentsToElasticSearch([
            $this->getArticleFixture(2),
            $this->getArticleFixture(3),
        ]);

        $this->newClient();
        $this->jsonRequest('GET', '/search');
        $response = $this->getJsonResponse();
        $this->assertEquals($response->items[0]->id, '15278');
        $this->assertEquals($response->items[1]->id, '15276');
    }

    public function test_boost_by_field()
    {
        $this->addDocumentsToElasticSearch([
            $this->getArticleFixture(4),
            $this->getArticleFixture(0),
        ]);

        $this->newClient();
        $this->jsonRequest('GET', '/search', [
                'for' => 'BUZZWORD', ]
        );
        $response = $this->getJsonResponse();
        $this->assertEquals($response->items[0]->id, '15279');
        $this->assertEquals($response->items[1]->id, '15275');
    }
}
