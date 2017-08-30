<?php

namespace tests\eLife\Search\Web;

/**
 * @group web
 */
final class ForTest extends ElasticTestCase
{
    public function test_all_for_words_match()
    {
        $this->addDocumentsToElasticSearch([
            $this->getArticleFixture(0),
            $this->getArticleFixture(1),
            $this->getArticleFixture(2),
            $this->getArticleFixture(3),
        ]);

        $this->newClient();
        $this->jsonRequest('GET', '/search', ['for' => 'Robert Smith']);
        $response = $this->getJsonResponse();
        $this->assertEquals($response->total, 2);
        $this->assertEquals($response->items[0]->id, '15278');
        $this->assertEquals($response->items[1]->id, '15276');
    }

    public function test_all_for_words_have_to_match()
    {
        $this->addDocumentsToElasticSearch([
            $this->getArticleFixture(0),
            $this->getArticleFixture(1),
            $this->getArticleFixture(2),
            $this->getArticleFixture(3),
        ]);

        $this->newClient();
        $this->jsonRequest('GET', '/search', ['for' => 'Robert Jones']);
        $response = $this->getJsonResponse();
        $this->assertEquals($response->total, 0);
    }

    public function test_all_for_words_fuzzy_match()
    {
        $this->addDocumentsToElasticSearch([
            $this->getArticleFixture(0),
            $this->getArticleFixture(1),
            $this->getArticleFixture(2),
            $this->getArticleFixture(3),
        ]);

        $this->newClient();
        $this->jsonRequest('GET', '/search', ['for' => 'Robert Smiht']);
        $response = $this->getJsonResponse();
        $this->assertEquals($response->total, 2);
        $this->assertEquals($response->items[0]->id, '15278');
        $this->assertEquals($response->items[1]->id, '15276');
    }
}
