<?php

namespace tests\eLife\Search\Web;

/**
 * @group web
 */
class FacetCountTest extends ElasticTestCase
{
    /**
     * @test
     */
    public function test_facet_count_is_accurate_after_filtering_subject()
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
