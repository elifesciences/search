<?php

namespace tests\eLife\Search\Web;

use PHPUnit\Framework\Attributes\Group;

#[Group('web')]
class PingTest extends WebTestCase
{
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
