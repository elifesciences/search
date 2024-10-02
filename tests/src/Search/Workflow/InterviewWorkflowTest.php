<?php

namespace tests\eLife\Search\Workflow;

use eLife\ApiSdk\Model\Interview;
use eLife\Search\Api\Elasticsearch\MappedElasticsearchClient;
use eLife\Search\Api\Elasticsearch\Response\IsDocumentResponse;
use eLife\Search\Api\HasSearchResultValidator;
use eLife\Search\Workflow\AbstractWorkflow;
use eLife\Search\Workflow\InterviewWorkflow;
use Exception;
use Mockery;
use Psr\Log\LoggerInterface;
use Symfony\Component\Serializer\Serializer;

final class InterviewWorkflowTest extends WorkflowTestCase
{
    protected function setWorkflow(
        Serializer $serializer,
        LoggerInterface $logger,
        MappedElasticsearchClient $client,
        HasSearchResultValidator $validator
    ) : AbstractWorkflow
    {
        return new InterviewWorkflow($serializer, $logger, $client, $validator);
    }

    protected function getModel() : string
    {
        return 'interview';
    }

    protected function getModelClass() : string
    {
        return Interview::class;
    }

    protected function getVersion() : int
    {
        return 1;
    }

    /**
     * @dataProvider workflowProvider
     * @test
     */
    public function testSerializationSmokeTest(Interview $interview)
    {
        // Mock the HTTP call that's made for subjects.
        $this->mockSubjects();
        // Check A to B
        $serialized = $this->workflow->serialize($interview);
        /** @var Interview $deserialized */
        $deserialized = $this->workflow->deserialize($serialized);
        $this->assertInstanceOf(Interview::class, $deserialized);
        // Check B to A
        $final_serialized = $this->workflow->serialize($deserialized);
        $this->assertJsonStringEqualsJsonString($serialized, $final_serialized);
    }

    /**
     * @dataProvider workflowProvider
     * @test
     */
    public function testIndexOfInterview(Interview $interview)
    {
        $return = $this->workflow->index($interview);
        $article = $return['json'];
        $id = $return['id'];
        $this->assertJson($article, 'Interview is not valid JSON');
        $this->assertNotNull($id, 'An ID is required.');
        $this->assertStringStartsWith('interview-', $id, 'ID should be assigned an appropriate prefix.');
    }

    /**
     * @dataProvider workflowProvider
     * @test
     */
    public function testInsertOfInterview(Interview $interview)
    {
        $this->elastic->shouldReceive('indexJsonDocument');
        $ret = $this->workflow->insert($this->workflow->serialize($interview), $interview->getId());
        $this->assertArrayHasKey('id', $ret);
        $id = $ret['id'];
        $this->assertEquals($interview->getId(), $id);
    }

    /**
     * @dataProvider workflowProvider
     * @test
     */
    public function testPostValidateOfInterview(Interview $interview)
    {
        $document = Mockery::mock(IsDocumentResponse::class);
        $this->elastic->shouldReceive('getDocumentById')
            ->once()
            ->with($interview->getId())
            ->andReturn($document);
        $document->shouldReceive('unwrap')
            ->once()
            ->andReturn([]);
        $this->validator->shouldReceive('validateSearchResult')
            ->once()
            ->andReturn(true);
        $ret = $this->workflow->postValidate($interview->getId());
        $this->assertEquals(1, $ret);
    }

    /**
     * @test
     */
    public function testPostValidateOfInterviewFailure()
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
