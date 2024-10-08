<?php

namespace tests\eLife\Search\Indexer\ModelIndexer;

use PHPUnit_Framework_TestCase;
use eLife\ApiSdk\Model\PodcastEpisode;
use eLife\Search\Indexer\ModelIndexer\PodcastEpisodeIndexer;
use tests\eLife\Search\HttpMocks;

final class PodcastEpisodeIndexerTest extends PHPUnit_Framework_TestCase
{
    use GetSerializer;
    use CallSerializer;
    use ModelProvider;
    use HttpMocks;

    /**
     * @var PodcastEpisodeIndexer
     */
    private $indexer;

    protected function setUp()
    {
        $this->indexer = new PodcastEpisodeIndexer($this->getSerializer());
    }

    protected function getModelDefinitions()
    {
        return [
            ['model' => 'podcast-episode', 'modelClass' => PodcastEpisode::class, 'version' => 1]
        ];
    }

    /**
     * @dataProvider modelProvider
     * @test
     */
    public function testSerializationSmokeTest(PodcastEpisode $podcastEpisode)
    {
        // Mock the HTTP call that's made for subjects.
        $this->mockSubjects();
        // Check A to B
        $serialized = $this->callSerialize($this->indexer, $podcastEpisode);
        /** @var PodcastEpisode $deserialized */
        $deserialized = $this->callDeserialize($this->indexer, $serialized);
        $this->assertInstanceOf(PodcastEpisode::class, $deserialized);
        // Check B to A
        $final_serialized = $this->callSerialize($this->indexer, $deserialized);
        $this->assertJsonStringEqualsJsonString($serialized, $final_serialized);
    }

    /**
     * @dataProvider modelProvider
     * @test
     */
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
