<?php

namespace tests\eLife\Search\Indexer\ModelIndexer;

use eLife\ApiSdk\Client\PodcastEpisodes;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use eLife\ApiSdk\Model\PodcastEpisode;
use eLife\Search\Indexer\ModelIndexer\PodcastEpisodeIndexer;

final class PodcastEpisodeIndexerTest extends TestCase
{
    use GetSerializer;
    use CallSerializer;
    use ModelProvider;

    /**
     * @var PodcastEpisodeIndexer
     */
    private $indexer;

    protected function setUp(): void
    {
        $this->indexer = new PodcastEpisodeIndexer($this->getSerializer());
    }

    protected static function getModelDefinitions(): array
    {
        return [
            ['model' => 'podcast-episode', 'modelClass' => PodcastEpisode::class, 'version' => PodcastEpisodes::VERSION_PODCAST_EPISODE]
        ];
    }

    #[DataProvider('modelProvider')]
    #[Test]
    public function testSerializationSmokeTest(PodcastEpisode $podcastEpisode)
    {
        // Check A to B
        $serialized = $this->callSerialize($this->indexer, $podcastEpisode);
        /** @var PodcastEpisode $deserialized */
        $deserialized = $this->callDeserialize($this->indexer, $serialized);
        $this->assertInstanceOf(PodcastEpisode::class, $deserialized);
        // Check B to A
        $final_serialized = $this->callSerialize($this->indexer, $deserialized);
        $this->assertJsonStringEqualsJsonString($serialized, $final_serialized);
    }

    #[DataProvider('modelProvider')]
    #[Test]
    public function testIndexOfPodcastEpisode(PodcastEpisode $podcastEpisode)
    {
        $changeSet = $this->indexer->prepareChangeSet($podcastEpisode);

        $this->assertCount(0, $changeSet->getDeletes());
        $this->assertCount(1, $changeSet->getInserts());

        $insert = $changeSet->getInserts()[0];
        $article = $insert['json'];
        $id = $insert['id'];
        $this->assertJson($article, 'PodcastEpisode is not valid JSON');
        $this->assertNotNull($id, 'An ID is required.');
        $this->assertStringStartsWith('podcast-episode-', $id, 'ID should be assigned an appropriate prefix.');
    }
}
