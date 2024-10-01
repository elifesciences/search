<?php

namespace tests\eLife\Search\Workflow;

use eLife\ApiSdk\Model\Collection;
use eLife\Search\Api\ApiValidator;
use eLife\Search\Api\Elasticsearch\MappedElasticsearchClient;
use eLife\Search\Api\Elasticsearch\Response\DocumentResponse;
use eLife\Search\Workflow\AbstractWorkflow;
use eLife\Search\Workflow\CollectionWorkflow;
use Exception;
use Mockery;
use Psr\Log\LoggerInterface;
use Symfony\Component\Serializer\Serializer;

final class CollectionWorkflowTest extends WorkflowTestCase
{
    protected function setWorkflow(
        Serializer $serializer,
        LoggerInterface $logger,
        MappedElasticsearchClient $client,
        ApiValidator $validator
    ) : AbstractWorkflow
    {
        return new CollectionWorkflow($serializer, $logger, $client, $validator);
    }

    protected function getModel() : string
    {
        return 'collection';
    }

    protected function getModelClass() : string
    {
        return Collection::class;
    }

    protected function getVersion() : int
    {
        return 2;
    }

    /**
     * @dataProvider workflowProvider
     * @test
     */
    public function testSerializationSmokeTest(Collection $collection)
    {
        // Mock the HTTP call that's made for subjects.
        $this->mockSubjects();
        // Check A to B
        $serialized = $this->workflow->serialize($collection);
        /** @var Collection $deserialized */
        $deserialized = $this->workflow->deserialize($serialized);
        $this->assertInstanceOf(Collection::class, $deserialized);
        // Check B to A
        $final_serialized = $this->workflow->serialize($deserialized);
        $this->assertJsonStringEqualsJsonString($serialized, $final_serialized);
    }

    /**
     * @dataProvider workflowProvider
     * @test
     */
    public function testIndexOfCollection(Collection $collection)
    {
        $return = $this->workflow->index($collection);
        $article = $return['json'];
        $id = $return['id'];
        $this->assertJson($article, 'Collection is not valid JSON');
        $this->assertNotNull($id, 'An ID is required.');
        $this->assertStringStartsWith('collection-', $id, 'ID should be assigned an appropriate prefix.');
    }

    /**
     * @dataProvider workflowProvider
     * @test
     */
    public function testInsertOfCollection(Collection $collection)
    {
        $this->elastic->shouldReceive('indexJsonDocument');
        $ret = $this->workflow->insert($this->workflow->serialize($collection), $collection->getId());
        $this->assertArrayHasKey('id', $ret);
        $id = $ret['id'];
        $this->assertEquals($collection->getId(), $id);
    }

    /**
     * @dataProvider workflowProvider
     * @test
     */
    public function testPostValidateOfCollection(Collection $collection)
    {
        $document = Mockery::mock(DocumentResponse::class);
        $this->elastic->shouldReceive('getDocumentById')
            ->once()
            ->with($collection->getId())
            ->andReturn($document);
        $document->shouldReceive('unwrap')
            ->once()
            ->andReturn([]);
        $this->validator->shouldReceive('validateSearchResult')
            ->once()
            ->andReturn(true);
        $ret = $this->workflow->postValidate($collection->getId());
        $this->assertEquals(1, $ret);
    }

    /**
     * @test
     */
    public function testPostValidateOfCollectionFailure()
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
}
