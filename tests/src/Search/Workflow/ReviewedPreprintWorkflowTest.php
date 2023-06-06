<?php

namespace tests\eLife\Search\Workflow;

use eLife\ApiSdk\Model\ReviewedPreprint;
use eLife\Search\Api\ApiValidator;
use eLife\Search\Api\Elasticsearch\MappedElasticsearchClient;
use eLife\Search\Workflow\ReviewedPreprintWorkflow;
use eLife\Search\Workflow\Workflow;
use Psr\Log\LoggerInterface;
use Symfony\Component\Serializer\Serializer;

final class ReviewedPreprintWorkflowTest extends WorkflowTestCase
{
    protected function setWorkflow(
        Serializer $serializer,
        LoggerInterface $logger,
        MappedElasticsearchClient $client,
        ApiValidator $validator
    ) : Workflow
    {
        return new ReviewedPreprintWorkflow($serializer, $logger, $client, $validator);
    }

    protected function getModel() : string
    {
        return 'reviewed-preprint';
    }

    protected function getModelClass() : string
    {
        return ReviewedPreprint::class;
    }

    protected function getVersion() : int
    {
        return 1;
    }

    /**
     * @dataProvider workflowProvider
     * @test
     */
    public function testSerializationSmokeTest(ReviewedPreprint $reviewedPreprint)
    {
        // Mock the HTTP call that's made for subjects.
        $this->mockSubjects();
        // Check A to B
        $serialized = $this->workflow->serialize($reviewedPreprint);
        /** @var BlogArticle $deserialized */
        $deserialized = $this->workflow->deserialize($serialized);
        $this->assertInstanceOf(ReviewedPreprint::class, $deserialized);
        // Check B to A
        $final_serialized = $this->workflow->serialize($deserialized);
        $this->assertJsonStringEqualsJsonString($serialized, $final_serialized);
    }

    /**
     * @dataProvider workflowProvider
     * @test
     */
    public function testIndexOfReviewedPreprint(ReviewedPreprint $reviewedPreprint)
    {
        $this->elastic->shouldReceive('getDocumentById')
            ->with('research-article-'.$reviewedPreprint->getId(), null, true)
            ->andReturn(null);
        $this->elastic->shouldReceive('getDocumentById')
            ->with('tools-resources-'.$reviewedPreprint->getId(), null, true)
            ->andReturn(null);
        $this->elastic->shouldReceive('getDocumentById')
            ->with('short-report-'.$reviewedPreprint->getId(), null, true)
            ->andReturn(null);
        $this->elastic->shouldReceive('getDocumentById')
            ->with('research-advance-'.$reviewedPreprint->getId(), null, true)
            ->andReturn(null);
        $return = $this->workflow->index($reviewedPreprint);
        $article = $return['json'];
        $id = $return['id'];
        $this->assertJson($article, 'Article is not valid JSON');
        $this->assertNotNull($id, 'An ID is required.');
        $this->assertStringStartsWith('reviewed-preprint-', $id, 'ID should be assigned an appropriate prefix.');
        $this->assertFalse($return['skipInsert']);
    }

    /**
     * @dataProvider workflowProvider
     * @test
     */
    public function testIndexOfReviewedPreprintSkipped(ReviewedPreprint $reviewedPreprint)
    {
        $this->elastic->shouldReceive('getDocumentById')
            ->with('research-article-'.$reviewedPreprint->getId(), null, true)
            ->andReturn('found');

        $this->assertSame([
            'json' => '',
            'id' => $reviewedPreprint->getId(),
            'skipInsert' => true,
        ], $this->workflow->index($reviewedPreprint));
    }

    /**
     * @dataProvider workflowProvider
     * @test
     */
    public function testInsertOfReviewedPreprint(ReviewedPreprint $reviewedPreprint)
    {
        $this->elastic->shouldReceive('indexJsonDocument');
        $ret = $this->workflow->insert($this->workflow->serialize($reviewedPreprint), $reviewedPreprint->getId(), false);
        $this->assertArrayHasKey('id', $ret);
        $id = $ret['id'];
        $this->assertEquals($reviewedPreprint->getId(), $id);
    }
}
