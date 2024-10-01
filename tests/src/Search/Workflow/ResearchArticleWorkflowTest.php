<?php

namespace tests\eLife\Search\Workflow;

use eLife\ApiSdk\Model\ArticlePoA;
use eLife\ApiSdk\Model\ArticleVersion;
use eLife\ApiSdk\Model\ArticleVoR;
use eLife\Search\Api\Elasticsearch\MappedElasticsearchClient;
use eLife\Search\Api\Elasticsearch\Response\DocumentResponse;
use eLife\Search\Api\HasSearchResultValidator;
use eLife\Search\Workflow\AbstractWorkflow;
use eLife\Search\Workflow\ResearchArticleWorkflow;
use Exception;
use Mockery;
use Psr\Log\LoggerInterface;
use Symfony\Component\Serializer\Serializer;
use tests\eLife\Search\ExceptionNullLogger;
use Traversable;

final class ResearchArticleWorkflowTest extends WorkflowTestCase
{
    protected function setWorkflow(
        Serializer $serializer,
        LoggerInterface $logger,
        MappedElasticsearchClient $client,
        HasSearchResultValidator $validator
    ): AbstractWorkflow
    {
        return new ResearchArticleWorkflow($serializer, $logger, $client, $validator);
    }

    /**
     * @dataProvider workflowProvider
     * @test
     */
    public function testSerializationSmokeTest(ArticleVersion $researchArticle)
    {
        // Mock the HTTP call that's made for subjects.
        $this->mockSubjects();
        // Check A to B
        $serialized = $this->workflow->serialize($researchArticle);
        /** @var ArticlePoA $deserialized */
        $deserialized = $this->workflow->deserialize($serialized);
        $this->assertInstanceOf(ArticleVersion::class, $deserialized);
        // Check B to A
        $final_serialized = $this->workflow->serialize($deserialized);
        $this->assertJsonStringEqualsJsonString($serialized, $final_serialized);
    }

    /**
     * @dataProvider workflowProvider
     * @test
     */
    public function testIndexOfResearchArticle(ArticleVersion $researchArticle)
    {
        $this->elastic->shouldReceive('deleteDocument');
        $return = $this->workflow->index($researchArticle);
        $article = $return['json'];
        $id = $return['id'];
        $this->assertJson($article, 'Article is not valid JSON');
        $this->assertNotNull($id, 'An ID is required.');
        $this->assertStringStartsWith('research-article-', $id, 'ID should be assigned an appropriate prefix.');
    }

    public function testStatusDateIsUsedAsTheSortDateWhenThereIsNoRdsArticle()
    {
        $this->workflow = new ResearchArticleWorkflow($this->getSerializer(), new ExceptionNullLogger(),
            $this->elastic, $this->validator, ['article-2' => ['date' => '2020-09-08T07:06:05Z']]);

        $article = $this->getArticle();

        $return = json_decode($this->workflow->index($article)['json'], true);

        $this->assertSame('2010-02-03T04:05:06Z', $return['sortDate']);
    }

    public function testRdsDateIsUsedAsTheSortDateWhenThereIsAnRdsArticle()
    {
        $this->workflow = new ResearchArticleWorkflow($this->getSerializer(), new ExceptionNullLogger(),
            $this->elastic, $this->validator, ['article-2' => ['date' => '2020-09-08T07:06:05Z']]);

        $article = $this->getArticle(2);

        $return = json_decode($this->workflow->index($article)['json'], true);

        $this->assertSame('2020-09-08T07:06:05Z', $return['sortDate']);
    }

    /**
     * @dataProvider workflowProvider
     * @test
     */
    public function testInsertOfResearchArticle(ArticleVersion $researchArticle)
    {
        // TODO: this should set up an expectation about actual ArticlePoA data being received, as passing in a BlogArticle doesn't break the test
        $this->elastic->shouldReceive('indexJsonDocument');
        $ret = $this->workflow->insert($this->workflow->serialize($researchArticle), $researchArticle->getId());
        $this->assertArrayHasKey('id', $ret);
        $id = $ret['id'];
        $this->assertEquals($researchArticle->getId(), $id);
    }

    /**
     * @dataProvider workflowProvider
     * @test
     */
    public function testPostValidateOfResearchArticle(ArticleVersion $researchArticle)
    {
        $document = Mockery::mock(DocumentResponse::class);
        $this->elastic->shouldReceive('getDocumentById')
            ->once()
            ->with($researchArticle->getId())
            ->andReturn($document);
        $document->shouldReceive('unwrap')
            ->once()
            ->andReturn([]);
        $this->validator->shouldReceive('validateSearchResult')
            ->once()
            ->andReturn(true);
        $ret = $this->workflow->postValidate($researchArticle->getId());
        $this->assertEquals(1, $ret);
    }

    /**
     * @test
     */
    public function testPostValidateOfResearchArticleFailure()
    {
        $document = Mockery::mock(DocumentResponse::class);
        $this->elastic->shouldReceive('getDocumentById')
            ->once()
            ->with('id')
            ->andReturn($document);
        $document->shouldReceive('unwrap')
            ->once()
            ->andReturn([]);
        $this->validator->shouldReceive('validateSearchResult')
            ->once()
            ->andThrow(Exception::class);
        $this->elastic->shouldReceive('deleteDocument')
            ->once()
            ->with('id');
        $ret = $this->workflow->postValidate('id');
        $this->assertEquals(-1, $ret);
    }

    public function workflowProvider(string $model = null, string $modelClass = null, int $version = null) : Traversable
    {
        foreach (array_merge(
            iterator_to_array(parent::workflowProvider('article-vor', ArticleVoR::class, 8)),
            iterator_to_array(parent::workflowProvider('article-poa', ArticlePoA::class, 4))
        ) as $k => $v) {
            yield $k => $v;
        }
    }

    private function getArticle($id = 1, $status = 'poa')
    {
        $sanitisedStatus = ($status === 'vor') ? 'vor' : 'poa';

        return $this->getSerializer()->denormalize(array_filter([
            'id' => 'article-'.$id,
            'stage' => 'published',
            'version' => 4,
            'type' => 'research-article',
            'doi' => 'DOI',
            'title' => 'title',
            'statusDate' => '2010-02-03T04:05:06Z',
            'reviewedDate' => '2020-09-08T07:06:05Z',
            'curationLabels' => ['foo', 'bar'],
            'volume' => 1,
            'elocationId' => 'elocationId',
            'copyright' => [
                'license' => 'license',
                'statement' => 'statement',
            ],
            'body' => ($sanitisedStatus === 'vor') ? [
                [
                    "type" => "section",
                    "id" => "s-1",
                    "title" => "Introduction",
                    "content" => [
                        [
                            "type" => "paragraph",
                            "text" => "Introduction text."
                        ]
                    ]
                ]
            ] : null,
            'status' => $sanitisedStatus,
        ]), ($sanitisedStatus === 'vor') ? ArticleVoR::class : ArticlePoA::class);
    }
}
