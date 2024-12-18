<?php

namespace tests\eLife\Search\Web;

use PHPUnit\Framework\Attributes\Group;
use stdClass;

#[Group('web')]
class DateRangeTest extends ElasticTestCase
{
    public function testDateRangeStartOnly()
    {
        $this->addDocumentsToElasticSearch([
            $this->getArticleFixture(0),
            $this->getArticleFixture(1),
            $this->getArticleFixture(2),
        ]);

        $this->newClient();
        $this->jsonRequest('GET', '/search', ['start-date' => '2016-12-13']);
        $response = $this->getJsonResponse();
        $this->assertIds(['15275', '15276'], $response);

        $this->jsonRequest('GET', '/search', ['start-date' => '2016-11-01']);
        $response = $this->getJsonResponse();
        $this->assertIds(['15275', '15276', '19662'], $response);

        // boundary is included
        $this->jsonRequest('GET', '/search', ['start-date' => '2016-12-19']);
        $response = $this->getJsonResponse();
        $this->assertIds(['15275', '15276'], $response, 'The date lower boundary is being excluded');
    }

    public function testDateRangeEndOnly()
    {
        $this->addDocumentsToElasticSearch([
            $this->getArticleFixture(0),
            $this->getArticleFixture(1),
            $this->getArticleFixture(2),
        ]);

        $this->newClient();
        $this->jsonRequest('GET', '/search', ['end-date' => '2016-12-13']);
        $response = $this->getJsonResponse();
        $this->assertIds(['19662'], $response);

        $this->jsonRequest('GET', '/search', ['end-date' => '2016-10-01']);
        $response = $this->getJsonResponse();
        $this->assertIds([], $response);

        // boundary
        $this->jsonRequest('GET', '/search', ['end-date' => '2016-12-05']);
        $response = $this->getJsonResponse();
        $this->assertIds(['19662'], $response, 'The date upper boundary is being excluded');
    }

    public function testDateRangeStartAndEnd()
    {
        $this->addDocumentsToElasticSearch([
            $this->getArticleFixture(0),
            $this->getArticleFixture(1),
            $this->getArticleFixture(2),
        ]);

        $this->newClient();
        $this->jsonRequest('GET', '/search', ['start-date' => '2016-11-01', 'end-date' => '2016-12-13']);
        $response = $this->getJsonResponse();
        $this->assertIds(['19662'], $response);
    }

    private function assertIds(array $expected, stdClass $response, $message = '')
    {
        $ids = [];
        foreach ($response->items as $item) {
            $ids[] = $item->id;
        }
        sort($ids);
        $this->assertEquals($expected, $ids, $message);
    }
}
