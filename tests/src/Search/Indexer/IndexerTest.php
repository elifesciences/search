<?php

namespace tests\eLife\Search\Indexer;

use eLife\ApiSdk\Model\HasIdentifier;
use eLife\ApiSdk\Model\Identifier;
use eLife\Search\Indexer\Indexer;
use eLife\Search\Api\Elasticsearch\MappedElasticsearchClient;
use eLife\Search\Api\Elasticsearch\Response\IsDocumentResponse;
use eLife\Search\Api\HasSearchResultValidator;
use eLife\ApiSdk\Model\Model;
use eLife\Search\Indexer\ChangeSet;
use eLife\Search\Indexer\ModelIndexer;
use Exception;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use tests\eLife\Search\ExceptionNullLogger;

final class IndexerTest extends TestCase
{
    protected $elastic;
    protected $validator;
    protected $mockArticleIndexer;
    protected $indexer;

    protected function setUp(): void
    {
        $this->elastic = Mockery::mock(MappedElasticsearchClient::class);
        $this->validator = Mockery::mock(HasSearchResultValidator::class);
        $this->mockArticleIndexer = Mockery::mock(ModelIndexer::class);

        $logger = new ExceptionNullLogger();

        $this->indexer = new Indexer($logger, $this->elastic, $this->validator, ['article' => $this->mockArticleIndexer]);
    }

    public function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    private function getMockEntity($type = 'article', $id = '1')
    {
        return new class($type, $id) implements Model, HasIdentifier {
            private $type;
            private $id;

            public function __construct($type, $id)
            {
                $this->type = $type;
                $this->id = $id;
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
        $this->mockArticleIndexer->shouldReceive('prepareChangeSet')
            ->once()
            ->with($entity)
            ->andReturn(new ChangeSet());
        $this->elastic->shouldNotReceive('indexJsonDocument');
        $this->elastic->shouldNotReceive('deleteDocument');
        $this->indexer->index($entity);

        // added to remove risky flag from this test, as all the assertions are done at `tearDown()`
        $this->assertTrue(true);
    }

    #[Test]
    public function testIndexSuccess()
    {
        $entity = $this->getMockEntity();
        $changeSet = new ChangeSet();
        $changeSet->addInsert('article/1', '{}');
        $this->mockArticleIndexer->shouldReceive('prepareChangeSet')
            ->once()
            ->with($entity)
            ->andReturn($changeSet);
        $this->elastic->shouldReceive('indexJsonDocument');
        $document = Mockery::mock(IsDocumentResponse::class);
        $this->elastic->shouldReceive('getDocumentById')
            ->once()
            ->with($entity->getIdentifier()->__toString())
            ->andReturn($document);
        /** @var \Mockery\Expectation $unwrapExpectation */
        $unwrapExpectation = $document->shouldReceive('unwrap');
        $unwrapExpectation
            ->once()
            ->andReturn([]);
        $this->validator->shouldReceive('validateSearchResult')
            ->once()
            ->andReturn(true);
        $this->indexer->index($entity);

        // added to remove risky flag from this test, as all the assertions are done at `tearDown()`
        $this->assertTrue(true);
    }


    #[Test]
    public function testPostValidateFailure()
    {
        $entity = $this->getMockEntity();
        $changeSet = new ChangeSet();
        $changeSet->addInsert('article/1', '{}');
        $this->mockArticleIndexer->shouldReceive('prepareChangeSet')
            ->once()
            ->with($entity)
            ->andReturn($changeSet);
        $this->elastic->shouldReceive('indexJsonDocument');
        $document = Mockery::mock(IsDocumentResponse::class);
        $this->elastic->shouldReceive('getDocumentById')
            ->once()
            ->with($entity->getIdentifier()->__toString())
            ->andReturn($document);
        /** @var \Mockery\Expectation $unwrapExpectation */
        $unwrapExpectation = $document->shouldReceive('unwrap');
        $unwrapExpectation
            ->once()
            ->andReturn([]);
        $this->validator->shouldReceive('validateSearchResult')
            ->once()
            ->andThrow(Exception::class);
        $this->elastic->shouldReceive('deleteDocument')
            ->once()
            ->with($entity->getIdentifier()->__toString());
        $this->expectException(\Exception::class);
        $this->indexer->index($entity);

        // added to remove risky flag from this test, as all the assertions are done at `tearDown()`
        $this->assertTrue(true);
    }

    #[Test]
    public function testIndexAndDeleteSuccess()
    {
        $entity = $this->getMockEntity();
        $changeSet = new ChangeSet();
        $changeSet->addInsert('article/1', '{}');
        $changeSet->addDelete('reviewed-preprint/1');
        $this->mockArticleIndexer->shouldReceive('prepareChangeSet')
            ->once()
            ->with($entity)
            ->andReturn($changeSet);
        $this->elastic->shouldReceive('indexJsonDocument');
        $document = Mockery::mock(IsDocumentResponse::class);
        $this->elastic->shouldReceive('getDocumentById')
            ->once()
            ->with($entity->getIdentifier()->__toString())
            ->andReturn($document);
        /** @var \Mockery\Expectation $unwrapExpectation */
        $unwrapExpectation = $document->shouldReceive('unwrap');
        $unwrapExpectation
            ->once()
            ->andReturn([]);
        $this->validator->shouldReceive('validateSearchResult')
            ->once()
            ->andReturn(true);
        $this->elastic->shouldReceive('deleteDocument')
            ->once()
            ->with('reviewed-preprint/1');
        $this->indexer->index($entity);

        // added to remove risky flag from this test, as all the assertions are done at `tearDown()`
        $this->assertTrue(true);
    }
}
