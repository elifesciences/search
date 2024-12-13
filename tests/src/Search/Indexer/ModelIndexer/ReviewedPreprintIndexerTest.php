<?php

namespace tests\eLife\Search\Indexer\ModelIndexer;

use eLife\ApiSdk\Model\ReviewedPreprint;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use eLife\Search\Api\Elasticsearch\MappedElasticsearchClient;
use eLife\Search\Indexer\ModelIndexer\ReviewedPreprintIndexer;
use Mockery;

final class ReviewedPreprintIndexerTest extends TestCase
{
    use GetSerializer;
    use CallSerializer;
    use ModelProvider;

    /**
     * @var MockInterface
     */
    private $elastic;

    /**
     * @var ReviewedPreprintIndexer
     */
    private $indexer;

    protected function setUp(): void
    {
        $this->elastic = Mockery::mock(MappedElasticsearchClient::class);
        $this->indexer = new ReviewedPreprintIndexer($this->getSerializer(), $this->elastic);
    }

    protected static function getModelDefinitions(): array
    {
        return [
            ['model' => 'reviewed-preprint', 'modelClass' => ReviewedPreprint::class, 'version' => 1]
        ];
    }

    #[DataProvider('modelProvider')]
    #[Test]
    public function testSerializationSmokeTest(ReviewedPreprint $reviewedPreprint)
    {
        // Check A to B
        $serialized = $this->callSerialize($this->indexer, $reviewedPreprint);
        /** @var ReviewedPreprint $deserialized */
        $deserialized = $this->callDeserialize($this->indexer, $serialized);
        $this->assertInstanceOf(ReviewedPreprint::class, $deserialized);
        // Check B to A
        $final_serialized = $this->callSerialize($this->indexer, $deserialized);
        $this->assertJsonStringEqualsJsonString($serialized, $final_serialized);
    }

    #[DataProvider('modelProvider')]
    #[Test]
    public function testIndexOfPodcastEpisode(ReviewedPreprint $reviewedPreprint)
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
        $changeSet = $this->indexer->prepareChangeSet($reviewedPreprint);

        $this->assertCount(0, $changeSet->getDeletes());
        $this->assertCount(1, $changeSet->getInserts());

        $insert = $changeSet->getInserts()[0];
        $article = $insert['json'];
        $id = $insert['id'];
        $this->assertJson($article, 'Article is not valid JSON');
        $this->assertNotNull($id, 'An ID is required.');
        $this->assertStringStartsWith('reviewed-preprint-', $id, 'ID should be assigned an appropriate prefix.');
    }


    #[DataProvider('modelProvider')]
    #[Test]
    public function testIndexOfReviewedPreprintSkipped(ReviewedPreprint $reviewedPreprint)
    {
        $this->elastic->shouldReceive('getDocumentById')
            ->with('research-article-'.$reviewedPreprint->getId(), null, true)
            ->andReturn('found');

        $changeSet = $this->indexer->prepareChangeSet($reviewedPreprint);

        $this->assertCount(0, $changeSet->getDeletes());
        $this->assertCount(0, $changeSet->getInserts());
    }

    #[DataProvider('modelProvider')]
    #[Test]
    public function testIndexOfReviewedPreprintSkippedToolsResources(ReviewedPreprint $reviewedPreprint)
    {
        $this->elastic->shouldReceive('getDocumentById')
            ->with('research-article-'.$reviewedPreprint->getId(), null, true)
            ->andReturn(null);
        $this->elastic->shouldReceive('getDocumentById')
            ->with('tools-resources-'.$reviewedPreprint->getId(), null, true)
            ->andReturn('found');

        $changeSet = $this->indexer->prepareChangeSet($reviewedPreprint);

        $this->assertCount(0, $changeSet->getDeletes());
        $this->assertCount(0, $changeSet->getInserts());
    }
}
