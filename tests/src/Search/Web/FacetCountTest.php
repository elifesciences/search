<?php

namespace tests\eLife\Search\Web;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;

#[Group('web')]
class FacetCountTest extends ElasticTestCase
{
    #[Test]
    public function testFacetCountIsAccurateAfterFilteringSubject()
    {
        $this->addDocumentsToElasticSearch([
            $this->getArticleFixture(0),
            $this->getArticleFixture(1),
            $this->getArticleFixture(2),
        ]);

        $this->newClient();
        $this->jsonRequest('GET', '/search', ['order' => 'asc', 'sort' => 'date', 'subject' => ['cell-biology']]);
        $response = $this->getJsonResponse();
        $subjects = array_filter($response->subjects, function ($subject) {
            return 'cell-biology' === $subject->id;
        });
        // The amount of research articles are still accurate.
        $this->assertEquals($response->types->{'research-article'}, 3, 'The amount of research article are still accurate');
        // The number of cell-biology is equal to the doc count
        $this->assertEquals(current($subjects)->results, 1, 'Only one result is returned for the subject');
        // The number of items in the results should be filtered.
        $this->assertEquals(count($response->items), 1, 'Only one result is in the body');
    }
}
