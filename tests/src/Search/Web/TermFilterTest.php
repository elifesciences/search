<?php

namespace src\Search\Web;

use PHPUnit\Framework\Attributes\Group;
use tests\eLife\Search\Web\ElasticTestCase;

#[Group('web')]
final class TermFilterTest extends ElasticTestCase
{
    public function testTermAndPrcFilter()
    {
        $this->addDocumentsToElasticSearch([
            $this->getArticleFixtureWithTerms(0, ['significance' => 1, 'strength' => 0], '00001'),
            $this->getArticleFixtureWithTerms(1, ['significance' => 0, 'strength' => 2], '00002'),
            $this->getArticleFixtureWithTerms(2, ['significance' => 999, 'strength' => 999], '00003'),
            $this->getArticleFixtureWithTerms(3, ['significance' => 999, 'strength' => 999], '00004'),
        ]);

        $this->newClient();
        $this->jsonRequest('GET', '/search', ['significance' => 'valuable']);
        $response = $this->getJsonResponse();
        $this->assertEquals(2, $response->total);

        $this->jsonRequest('GET', '/search', ['significance' => 'useful']);
        $response = $this->getJsonResponse();
        $this->assertEquals(3, $response->total);

        $this->jsonRequest('GET', '/search', ['prc' => '1']);
        $response = $this->getJsonResponse();
        $this->assertEquals(2, $response->total);

        $this->jsonRequest('GET', '/search', ['prc' => '1', 'strength' => 'solid']);
        $response = $this->getJsonResponse();
        $this->assertEquals(0, $response->total);
    }
}
