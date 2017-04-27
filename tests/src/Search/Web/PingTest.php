<?php

namespace tests\eLife\Search\Web;

use Silex\WebTestCase;

class PingTest extends ElasticTestCase
{
    // avoid dependency on ElasticSearch for this test
    public function setUp()
    {
        WebTestCase::setUp();
    }

    public function tearDown()
    {
        WebTestCase::setUp();
    }

    public function testItIsNotCached()
    {
        $this->newClient();
        $this->api->request('GET', '/ping');
        $response = $this->getResponse();
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('text/plain; charset=UTF-8', $response->headers->get('Content-Type'));
        $this->assertEquals('must-revalidate, no-cache, no-store, private', $response->headers->get('Cache-Control'));
    }
}
