<?php

namespace tests\eLife\Search\Workflow;

use eLife\ApiSdk\Model\HasIdentifier;
use eLife\Search\Api\Elasticsearch\MappedElasticsearchClient;
use eLife\Search\Api\Elasticsearch\Response\IsDocumentResponse;
use eLife\Search\Api\HasSearchResultValidator;
use eLife\Search\Workflow\AbstractWorkflow;
use eLife\ApiSdk\Model\Model;
use Exception;
use Mockery;
use Psr\Log\LoggerInterface;
use Symfony\Component\Serializer\Serializer;

final class AbstractWorkflowTest extends WorkflowTestCase
{
    protected function setWorkflow(
        Serializer $serializer,
        LoggerInterface $logger,
        MappedElasticsearchClient $client,
        HasSearchResultValidator $validator
    ) : AbstractWorkflow
    {
        return new class($serializer, $logger, $client, $validator) extends AbstractWorkflow {
            public function prepare($model)
            {
                return ['json' => '{"some":"json"}', 'id' => $model->getIdentifier(), 'skipInsert' => $model->skipInsert];
            }
        };
    }

    private function getMockEntity($type = 'generic-model', $id = '1')
    {
        return new class($type, $id) implements Model {
            private $type;
            private $id;
            public $skipInsert = false;
            public function __construct($type, $id)
            {
                $this->type = $type;
                $this->id = $id;
            }
            public function getIdentifier()
            {
                return "$this->type/$this->id";
            }

            public function getTitle()
            {
                return 'A generic piece of content with a title';
            }
        };
    }

    /**
     * @test
     */
    public function testSkipInsert()
    {
        $entity = $this->getMockEntity();
        $entity->skipInsert = true;
        $this->elastic->shouldNotReceive('indexJsonDocument');
        $this->workflow->run($entity);
    }

    /**
     * @test
     */
    public function testIndexOfGenericModelSuccess()
    {
        $entity = $this->getMockEntity();
        $this->elastic->shouldReceive('indexJsonDocument');
        $document = Mockery::mock(IsDocumentResponse::class);
        $this->elastic->shouldReceive('getDocumentById')
            ->once()
            ->with($entity->getIdentifier())
            ->andReturn($document);
        $document->shouldReceive('unwrap')
            ->once()
            ->andReturn([]);
        $this->validator->shouldReceive('validateSearchResult')
            ->once()
            ->andReturn(true);
        $this->workflow->run($entity);
    }


    /**
     * @test
     */
    public function testPostValidateOfBlogArticleFailure()
    {
        $entity = $this->getMockEntity();
        $this->elastic->shouldReceive('indexJsonDocument');
        $document = Mockery::mock(IsDocumentResponse::class);
        $this->elastic->shouldReceive('getDocumentById')
            ->once()
            ->with($entity->getIdentifier())
            ->andReturn($document);
        $document->shouldReceive('unwrap')
            ->once()
            ->andReturn([]);
        $this->validator->shouldReceive('validateSearchResult')
            ->once()
            ->andThrow(Exception::class);
        $this->elastic->shouldReceive('deleteDocument')
            ->once()
            ->with($entity->getIdentifier());
        $this->expectException(\Exception::class);
        $this->workflow->run($entity);
    }
}
