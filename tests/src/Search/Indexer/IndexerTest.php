<?php

namespace tests\eLife\Search\Indexer;

use eLife\ApiSdk\Model\HasIdentifier;
use eLife\ApiSdk\Model\Identifier;
use eLife\Search\Indexer\Indexer;
use eLife\Search\Api\Elasticsearch\MappedElasticsearchClient;
use eLife\Search\Api\Elasticsearch\Response\IsDocumentResponse;
use eLife\Search\Api\HasSearchResultValidator;
use eLife\ApiSdk\Model\Model;
use eLife\Search\Api\Elasticsearch\Response\ElasticResponse;
use eLife\Search\Api\Elasticsearch\Response\SuccessResponse;
use eLife\Search\Indexer\ChangeSet;
use eLife\Search\Indexer\ModelIndexer;
use Exception;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use tests\eLife\Search\ExceptionNullLogger;

final class IndexerTest extends TestCase
{
    protected MockInterface&MappedElasticsearchClient $elastic;
    protected MockInterface&HasSearchResultValidator $validator;
    protected MockInterface&ModelIndexer $mockArticleIndexer;
    protected Indexer $indexer;

    protected function setUp(): void
    {
        /** @var MockInterface&MappedElasticsearchClient $elastic */
        $elastic = Mockery::mock(MappedElasticsearchClient::class);
        $this->elastic = $elastic;
        /** @var MockInterface&HasSearchResultValidator $validator */
        $validator = Mockery::mock(HasSearchResultValidator::class);
        $this->validator = $validator;
        /** @var MockInterface&ModelIndexer $mockArticleIndexer */
        $mockArticleIndexer = Mockery::mock(ModelIndexer::class);
        $this->mockArticleIndexer = $mockArticleIndexer;

        $logger = new ExceptionNullLogger();

        $this->indexer = new Indexer($logger, $this->elastic, $this->validator, ['article' => $this->mockArticleIndexer]);
    }

    public function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    private function getMockEntity(string $type = 'article', string $id = '1')
    {
        return new class($type, $id) implements Model, HasIdentifier {
            public function __construct(
                private string $type,
                private string $id,
            ) {
            }
            public function getIdentifier(): Identifier
            {
                return Identifier::fromString("{$this->type}/{$this->id}");
            }
        };
    }

    #[Test]
    public function testSkipInsert()
    {
        $entity = $this->getMockEntity();
        /** @var \Mockery\Expectation $prepareChangeSetExpectation */
        $prepareChangeSetExpectation = $this->mockArticleIndexer->shouldReceive('prepareChangeSet');
        $prepareChangeSetExpectation
            ->once()
            ->with($entity)
            ->andReturn(new ChangeSet());
        $this->elastic->shouldNotReceive('indexJsonDocument');
        $this->elastic->shouldNotReceive('deleteDocument');
        $this->indexer->index($entity);

        // added to remove risky flag from this test, as all the assertions are done at `tearDown()`
        $this->expectNotToPerformAssertions();
    }

    #[Test]
    public function testIndexSuccess()
    {
        $entity = $this->getMockEntity();
        $changeSet = new ChangeSet();
        $changeSet->addInsert('article/1', '{}');

        /** @var \Mockery\Expectation $prepareChangeSetExpectation */
        $prepareChangeSetExpectation = $this->mockArticleIndexer->shouldReceive('prepareChangeSet');
        $prepareChangeSetExpectation
            ->once()
            ->with($entity)
            ->andReturn($changeSet);

        /** @var \Mockery\Expectation $indexJsonDocumentExpectation */
        $indexJsonDocumentExpectation = $this->elastic->shouldReceive('indexJsonDocument');
        $indexJsonDocumentExpectation
            ->once()
            ->andReturn(new SuccessResponse);

        $document = Mockery::mock(IsDocumentResponse::class.','.ElasticResponse::class);
        /** @var \Mockery\Expectation $getDocumentByIdExpectation */
        $getDocumentByIdExpectation = $this->elastic->shouldReceive('getDocumentById');
        $getDocumentByIdExpectation
            ->once()
            ->with($entity->getIdentifier()->__toString())
            ->andReturn($document);

        /** @var \Mockery\Expectation $unwrapExpectation */
        $unwrapExpectation = $document->shouldReceive('unwrap');
        $unwrapExpectation
            ->once()
            ->andReturn([]);

        /** @var \Mockery\Expectation $validateSearchResultExpectation */
        $validateSearchResultExpectation = $this->validator->shouldReceive('validateSearchResult');
        $validateSearchResultExpectation
            ->once()
            ->andReturn(true);
        $this->indexer->index($entity);

        // added to remove risky flag from this test, as all the assertions are done at `tearDown()`
        $this->expectNotToPerformAssertions();
    }


    #[Test]
    public function testPostValidateFailure()
    {
        $entity = $this->getMockEntity();
        $changeSet = new ChangeSet();
        $changeSet->addInsert('article/1', '{}');

        /** @var \Mockery\Expectation $prepareChangeSetExpectation */
        $prepareChangeSetExpectation = $this->mockArticleIndexer->shouldReceive('prepareChangeSet');
        $prepareChangeSetExpectation
            ->once()
            ->with($entity)
            ->andReturn($changeSet);

        /** @var \Mockery\Expectation $indexJsonDocumentExpectation */
        $indexJsonDocumentExpectation = $this->elastic->shouldReceive('indexJsonDocument');
        $indexJsonDocumentExpectation
            ->once()
            ->andReturn(new SuccessResponse);

        $document = Mockery::mock(IsDocumentResponse::class.','.ElasticResponse::class);
        /** @var \Mockery\Expectation $getDocumentByIdExpectation */
        $getDocumentByIdExpectation = $this->elastic->shouldReceive('getDocumentById');
        $getDocumentByIdExpectation
            ->once()
            ->with($entity->getIdentifier()->__toString())
            ->andReturn($document);
        /** @var \Mockery\Expectation $unwrapExpectation */
        $unwrapExpectation = $document->shouldReceive('unwrap');
        $unwrapExpectation
            ->once()
            ->andReturn([]);
        /** @var \Mockery\Expectation $validateSearchResultExpectation */
        $validateSearchResultExpectation = $this->validator->shouldReceive('validateSearchResult');
        $validateSearchResultExpectation
            ->once()
            ->andThrow(Exception::class);

        /** @var \Mockery\Expectation $deleteDocumentExpectation */
        $deleteDocumentExpectation = $this->elastic->shouldReceive('deleteDocument');
        $deleteDocumentExpectation
            ->once()
            ->with($entity->getIdentifier()->__toString());
        $this->expectException(\Exception::class);
        $this->indexer->index($entity);

        // added to remove risky flag from this test, as all the assertions are done at `tearDown()`
        $this->expectNotToPerformAssertions();
    }

    #[Test]
    public function testIndexAndDeleteSuccess()
    {
        $entity = $this->getMockEntity();
        $changeSet = new ChangeSet();
        $changeSet->addInsert('article/1', '{}');
        $changeSet->addDelete('reviewed-preprint/1');
        /** @var \Mockery\Expectation $prepareChangeSetExpectation */
        $prepareChangeSetExpectation = $this->mockArticleIndexer->shouldReceive('prepareChangeSet');
        $prepareChangeSetExpectation
            ->once()
            ->with($entity)
            ->andReturn($changeSet);

        /** @var \Mockery\Expectation $indexJsonDocumentExpectation */
        $indexJsonDocumentExpectation = $this->elastic->shouldReceive('indexJsonDocument');
        $indexJsonDocumentExpectation
            ->once()
            ->andReturn(new SuccessResponse);

        $document = Mockery::mock(IsDocumentResponse::class.','.ElasticResponse::class);
        /** @var \Mockery\Expectation $getDocumentByIdExpectation */
        $getDocumentByIdExpectation = $this->elastic->shouldReceive('getDocumentById');
        $getDocumentByIdExpectation
            ->once()
            ->with($entity->getIdentifier()->__toString())
            ->andReturn($document);

        /** @var \Mockery\Expectation $unwrapExpectation */
        $unwrapExpectation = $document->shouldReceive('unwrap');
        $unwrapExpectation
            ->once()
            ->andReturn([]);


        /** @var \Mockery\Expectation $validateSearchResultExpectation */
        $validateSearchResultExpectation = $this->validator->shouldReceive('validateSearchResult');
        $validateSearchResultExpectation
            ->once()
            ->andReturn(true);

        /** @var \Mockery\Expectation $deleteDocumentExpectation */
        $deleteDocumentExpectation = $this->elastic->shouldReceive('deleteDocument');
        $deleteDocumentExpectation
            ->once()
            ->with('reviewed-preprint/1');
        $this->indexer->index($entity);

        // added to remove risky flag from this test, as all the assertions are done at `tearDown()`
        $this->expectNotToPerformAssertions();
    }
}
