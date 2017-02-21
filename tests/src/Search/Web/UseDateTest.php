<?php

namespace tests\eLife\Search\Web;

class UseDateTest extends ElasticTestCase
{
    public function test_use_date()
    {
        $this->addDocumentsToElasticSearch([
            $this->getCollectionFixture(0),
            $this->getArticleFixture(0),
        ]);

        $this->newClient();
        $this->jsonRequest('GET', '/search', ['use-date' => 'default', 'order' => 'asc', 'sort' => 'date']);
        $response = $this->getJsonResponse();
        $this->assertEquals($response->items[0]->id, '15275');
        $this->assertEquals($response->items[1]->id, '3a8bbf09');

        $this->newClient();
        $this->jsonRequest('GET', '/search', ['use-date' => 'published', 'order' => 'asc', 'sort' => 'date']);
        $response = $this->getJsonResponse();
        $this->assertEquals($response->items[0]->id, '3a8bbf09');
        $this->assertEquals($response->items[1]->id, '15275');
    }

    public function test_use_date_range()
    {
        $this->addDocumentsToElasticSearch([
            $this->getCollectionFixture(0),
            $this->getArticleFixture(0),
        ]);

        $this->newClient();
        $this->jsonRequest('GET', '/search', ['start-date' => '2016-01-01']);
        $response = $this->getJsonResponse();
        $this->assertEquals(2, $response->total);

        $this->newClient();
        $this->jsonRequest('GET', '/search', ['start-date' => '2016-01-01', 'use-date' => 'published']);
        $response = $this->getJsonResponse();
        $this->assertEquals(1, $response->total);
        $this->assertEquals('research-article', $response->items[0]->type);

        $this->newClient();
        $this->jsonRequest('GET', '/search', ['end-date' => '2016-01-01']);
        $response = $this->getJsonResponse();
        $this->assertEquals(0, $response->total);

        $this->newClient();
        $this->jsonRequest('GET', '/search', ['end-date' => '2016-01-01', 'use-date' => 'published']);
        $response = $this->getJsonResponse();
        $this->assertEquals(1, $response->total);
        $this->assertEquals('collection', $response->items[0]->type);
    }
}
