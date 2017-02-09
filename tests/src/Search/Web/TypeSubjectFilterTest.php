<?php

namespace tests\eLife\Search\Web;

/**
 * @group web
 */
class TypeSubjectFilterTest extends ElasticTestCase
{
    public function test_subject_filtering_works()
    {
        $this->addDocumentsToElasticSearch([
            $this->getArticleFixture(0),
            $this->getArticleFixture(1),
            $this->getArticleFixture(2),
            $this->getArticleFixture(3),
        ]);

        $this->newClient();
        $this->jsonRequest('GET', '/search', ['subject' => ['neuroscience']]);
        $response = $this->getJsonResponse();
        $this->assertEquals($response->total, 3);

        $this->jsonRequest('GET', '/search', ['subject' => ['immunology']]);
        $response = $this->getJsonResponse();
        $this->assertEquals($response->total, 2);

        $this->jsonRequest('GET', '/search', ['subject' => ['neuroscience', 'immunology']]);
        $response = $this->getJsonResponse();
        $this->assertEquals($response->total, 4);
    }

    public function test_type_filtering_works()
    {
        $this->addDocumentsToElasticSearch([
            $this->getArticleFixture(0),
            $this->getArticleFixture(1),
            $this->getArticleFixture(2),
            $this->getArticleFixture(3),
        ]);

        $this->newClient();
        $this->jsonRequest('GET', '/search', ['type' => ['research-article']]);
        $response = $this->getJsonResponse();
        $this->assertEquals($response->total, 3);

        $this->jsonRequest('GET', '/search', ['type' => ['correction']]);
        $response = $this->getJsonResponse();
        $this->assertEquals($response->total, 1);

        $this->jsonRequest('GET', '/search', ['type' => ['research-article', 'correction']]);
        $response = $this->getJsonResponse();
        $this->assertEquals($response->total, 4);
    }

    public function test_subject_and_type_filtering_works()
    {
        $this->addDocumentsToElasticSearch([
            $this->getArticleFixture(0),
            $this->getArticleFixture(1),
            $this->getArticleFixture(2),
            $this->getArticleFixture(3),
        ]);

        $this->newClient();
        $this->jsonRequest('GET', '/search', [
            'subject' => ['immunology'],
            'type' => ['research-article'],
        ]);
        $response = $this->getJsonResponse();
        $this->assertEquals($response->total, 1);

        $this->newClient();
        $this->jsonRequest('GET', '/search', [
            'subject' => ['immunology', 'neuroscience'],
            'type' => ['research-article'],
        ]);
        $response = $this->getJsonResponse();
        $this->assertEquals($response->total, 3);

        $this->newClient();
        $this->jsonRequest('GET', '/search', [
            'subject' => ['immunology'],
            'type' => ['research-article', 'correction'],
        ]);
        $response = $this->getJsonResponse();
        $this->assertEquals($response->total, 2);

        $this->newClient();
        $this->jsonRequest('GET', '/search', [
            'subject' => ['immunology', 'neuroscience'],
            'type' => ['research-article', 'correction'],
        ]);
        $response = $this->getJsonResponse();
        $this->assertEquals($response->total, 4);
    }

    public function test_for_subject_and_type_filtering_works()
    {
        $this->addDocumentsToElasticSearch([
            $this->getArticleFixture(0),
            $this->getArticleFixture(1),
            $this->getArticleFixture(2),
            $this->getArticleFixture(3),
        ]);

        $this->newClient();
        $this->jsonRequest('GET', '/search', [
            'for' => 'BUZZWORD ONLY FOUND HERE',
            'subject' => ['neuroscience'],
            'type' => ['research-article'],
        ]);
        $response = $this->getJsonResponse();
        $this->assertEquals($response->total, 1);

        $foundImmunology = false;
        array_walk($response->subjects, function ($subject) use (&$foundImmunology) {
            if ($subject->id === 'immunology') {
                $foundImmunology = true;
                $this->assertEquals($subject->count, 0);
            }
        });

        $this->assertTrue($foundImmunology, 'Did not find empty subject');
    }
}
