<?php

namespace tests\eLife\Search;

/**
 * @group web
 */
class SortOrderTest extends ElasticTestCase
{
    /**
     * @test
     */
    public function testDateOrder()
    {
        $this->addDocumentsToElasticSearch([
            $this->getArticleFixture(0),
            $this->getArticleFixture(1),
        ]);

        $this->newClient();
        $this->jsonRequest('GET', '/search', ['order' => 'asc', 'sort' => 'date']);
        $response = $this->getJsonResponse();
        $this->assertEquals($response->items[0]->id, '19662');
        $this->assertEquals($response->items[1]->id, '15275');

        $this->newClient();
        $this->jsonRequest('GET', '/search', ['order' => 'desc', 'sort' => 'date']);
        $response = $this->getJsonResponse();
        $this->assertEquals($response->items[0]->id, '15275');
        $this->assertEquals($response->items[1]->id, '19662');
    }
}
