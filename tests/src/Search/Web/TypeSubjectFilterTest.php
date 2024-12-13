<?php

namespace tests\eLife\Search\Web;

use PHPUnit\Framework\Attributes\Group;

#[Group('web')]
class TypeSubjectFilterTest extends ElasticTestCase
{
    public function testSubjectFilteringWorks()
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

    public function testTypeFilteringWorks()
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

    public function testSubjectAndTypeFilteringWorks()
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

    public function testForSubjectAndTypeFilteringWorks()
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
            if ('immunology' === $subject->id) {
                $foundImmunology = true;
                $this->assertEquals($subject->results, 0);
            }
        });

        $this->assertTrue($foundImmunology, 'Did not find empty subject');
    }
}
