<?php

namespace tests\eLife\Search\Web;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;

#[Group('web')]
class BoostTest extends ElasticTestCase
{
    #[Test]
    public function testBoostByType()
    {
        $this->addDocumentsToElasticSearch([
            $this->getArticleFixture(2),
            $this->getArticleFixture(3),
        ]);

        $this->newClient();
        $this->jsonRequest('GET', '/search');
        $response = $this->getJsonResponse();

        $this->markTestIncomplete('This needs more investigation to eliminate factors other that type affecting relevance scores.');
        $this->assertEquals($response->items[0]->id, '15278');
        $this->assertEquals($response->items[1]->id, '15276');
    }

    public function testBoostByField()
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

    public function testBoostAppliesWhenAllWordsMatch()
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

    public function testBoostAppliesWhenAllWordsFuzzyMatch()
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
