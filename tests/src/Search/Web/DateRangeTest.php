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
        $this->jsonRequest('GET', '/search', ['start-date' => '2016-12-01']);
        $response = $this->getJsonResponse();
        $this->assertEquals($response->total, 2);

        $this->jsonRequest('GET', '/search', ['start-date' => '2016-11-01']);
        $response = $this->getJsonResponse();
        $this->assertEquals($response->total, 3);
    }

    public function test_date_range_end_only()
    {
        $this->addDocumentsToElasticSearch([
            $this->getArticleFixture(0),
            $this->getArticleFixture(1),
            $this->getArticleFixture(2),
        ]);

        $this->newClient();
        $this->jsonRequest('GET', '/search', ['end-date' => '2016-12-01']);
        $response = $this->getJsonResponse();
        $this->assertEquals($response->total, 1);

        $this->jsonRequest('GET', '/search', ['end-date' => '2016-10-01']);
        $response = $this->getJsonResponse();
        $this->assertEquals($response->total, 0);
    }

    public function test_date_range_start_and_end()
    {
        $this->addDocumentsToElasticSearch([
            $this->getArticleFixture(0),
            $this->getArticleFixture(1),
            $this->getArticleFixture(2),
        ]);

        $this->newClient();
        $this->jsonRequest('GET', '/search', ['start-date' => '2016-11-01', 'end-date' => '2016-12-01']);
        $response = $this->getJsonResponse();
        $this->assertEquals($response->total, 1);
    }
}
