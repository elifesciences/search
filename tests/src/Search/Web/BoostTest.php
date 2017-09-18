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
        $this->jsonRequest('GET', '/search', ['for' => 'BUZZWORD']);
        $response = $this->getJsonResponse();
        $this->assertEquals($response->items[0]->id, '15279');
        $this->assertEquals($response->items[1]->id, '15275');
    }

    public function test_boost_applies_when_all_words_match()
    {
        $this->addDocumentsToElasticSearch([
            $this->getArticleFixture(0),
            $this->getArticleFixture(3),
            $this->getArticleFixture(4),
        ]);

        $this->newClient();
        $this->jsonRequest('GET', '/search', ['for' => 'Robert Smith']);
        $response = $this->getJsonResponse();
        $this->assertEquals($response->total, 2);
        $this->assertEquals($response->items[0]->id, '15278');
        $this->assertEquals($response->items[1]->id, '15279');
    }

    public function test_boost_applies_when_all_words_fuzzy_match()
    {
        $this->addDocumentsToElasticSearch([
            $this->getArticleFixture(0),
            $this->getArticleFixture(3),
            $this->getArticleFixture(4),
        ]);

        $this->newClient();
        $this->jsonRequest('GET', '/search', ['for' => 'Robert Smiht']);
        $response = $this->getJsonResponse();
        $this->assertEquals($response->total, 2);
        $this->assertEquals($response->items[0]->id, '15278');
        $this->assertEquals($response->items[1]->id, '15279');
    }
}
