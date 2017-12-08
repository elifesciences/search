<?php

namespace tests\eLife\Search\Web;

/**
 * @group web
 */
class DateRangeTest extends ElasticTestCase
{
    public function test_date_range_start_only()
    {
        $this->addDocumentsToElasticSearch([
            $this->getArticleFixture(0),
            $this->getArticleFixture(1),
            $this->getArticleFixture(2),
        ]);

        $this->newClient();
        $this->jsonRequest('GET', '/search', ['start-date' => '2016-12-13']);
        $response = $this->getJsonResponse();
        $this->assertEquals(2, $response->total);

        $this->jsonRequest('GET', '/search', ['start-date' => '2016-11-01']);
        $response = $this->getJsonResponse();
        $this->assertEquals(3, $response->total);

        // boundary is included
        $this->jsonRequest('GET', '/search', ['start-date' => '2016-12-19']);
        $response = $this->getJsonResponse();
        $this->assertEquals(2, $response->total, "The date lower boundary is being excluded");
    }

    public function test_date_range_end_only()
    {
        $this->addDocumentsToElasticSearch([
            $this->getArticleFixture(0),
            $this->getArticleFixture(1),
            $this->getArticleFixture(2),
        ]);

        $this->newClient();
        $this->jsonRequest('GET', '/search', ['end-date' => '2016-12-13']);
        $response = $this->getJsonResponse();
        $this->assertEquals(1, $response->total);

        $this->jsonRequest('GET', '/search', ['end-date' => '2016-10-01']);
        $response = $this->getJsonResponse();
        $this->assertEquals(0, $response->total);

        // boundary
        $this->jsonRequest('GET', '/search', ['end-date' => '2016-12-05']);
        $response = $this->getJsonResponse();
        $this->assertEquals(1, $response->total, "The date upper boundary is being excluded");
    }

    public function test_date_range_start_and_end()
    {
        $this->addDocumentsToElasticSearch([
            $this->getArticleFixture(0),
            $this->getArticleFixture(1),
            $this->getArticleFixture(2),
        ]);

        $this->newClient();
        $this->jsonRequest('GET', '/search', ['start-date' => '2016-11-01', 'end-date' => '2016-12-13']);
        $response = $this->getJsonResponse();
        $this->assertEquals(1, $response->total);
    }
}
