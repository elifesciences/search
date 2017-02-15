<?php

namespace tests\eLife\Search\Web;

/**
 * @group failing
 */
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
}
