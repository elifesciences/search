<?php

namespace tests\eLife\Search\Workflow;

use eLife\ApiSdk\Model\LabsPost;
use eLife\Search\Api\Elasticsearch\MappedElasticsearchClient;
use eLife\Search\Api\Elasticsearch\Response\IsDocumentResponse;
use eLife\Search\Api\HasSearchResultValidator;
use eLife\Search\Workflow\AbstractWorkflow;
use eLife\Search\Workflow\LabsPostWorkflow;
use Exception;
use Mockery;
use Psr\Log\LoggerInterface;
use Symfony\Component\Serializer\Serializer;

final class LabsPostWorkflowTest extends WorkflowTestCase
{
    protected function setWorkflow(
        Serializer $serializer,
        LoggerInterface $logger,
        MappedElasticsearchClient $client,
        HasSearchResultValidator $validator
    ) : AbstractWorkflow
    {
        return new LabsPostWorkflow($serializer, $logger, $client, $validator);
    }

    protected function getModelClass(): string
    {
        return LabsPost::class;
    }

    protected function getModel(): string
    {
        return 'labs-post';
    }

    protected function getVersion() : int
    {
        return 2;
    }

    /**
     * @dataProvider workflowProvider
     * @test
     */
    public function testSerializationSmokeTest(LabsPost $labsPost)
    {
        // Mock the HTTP call that's made for subjects.
        $this->mockSubjects();
        // Check A to B
        $serialized = $this->workflow->serialize($labsPost);
        /** @var LabsPost $deserialized */
        $deserialized = $this->workflow->deserialize($serialized);
        $this->assertInstanceOf(LabsPost::class, $deserialized);
        // Check B to A
        $final_serialized = $this->workflow->serialize($deserialized);
        $this->assertJsonStringEqualsJsonString($serialized, $final_serialized);
    }

    /**
     * @dataProvider workflowProvider
     * @test
     */
    public function testIndexOfLabsPost(LabsPost $labsPost)
    {
        $return = $this->workflow->index($labsPost);
        $article = $return['json'];
        $id = $return['id'];
        $this->assertJson($article, 'LabsPost is not valid JSON');
        $this->assertNotNull($id, 'An ID is required.');
        $this->assertStringStartsWith('labs-post-', $id, 'ID should be assigned an appropriate prefix.');
    }

    /**
     * @dataProvider workflowProvider
     * @test
     */
    public function testInsertOfLabsPost(LabsPost $labsPost)
    {
        $this->elastic->shouldReceive('indexJsonDocument');
        $ret = $this->workflow->insert($this->workflow->serialize($labsPost), $labsPost->getId());
        $this->assertArrayHasKey('id', $ret);
        $id = $ret['id'];
        $this->assertEquals($labsPost->getId(), $id);
    }

    /**
     * @dataProvider workflowProvider
     * @test
     */
    public function testPostValidateOfLabsPost(LabsPost $labsPost)
    {
        $document = Mockery::mock(IsDocumentResponse::class);
        $this->elastic->shouldReceive('getDocumentById')
            ->once()
            ->with($labsPost->getId())
            ->andReturn($document);
        $document->shouldReceive('unwrap')
            ->once()
            ->andReturn([]);
        $this->validator->shouldReceive('validateSearchResult')
            ->once()
            ->andReturn(true);
        $ret = $this->workflow->postValidate($labsPost->getId());
        $this->assertEquals(1, $ret);
    }

    /**
     * @test
     */
    public function testPostValidateOfLabsPostFailure()
    {
        $document = Mockery::mock(IsDocumentResponse::class);
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
}
