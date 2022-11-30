<?php

namespace tests\eLife\Search\Workflow;

use eLife\ApiSdk\Mode\FundingAward;
use eLife\ApiSdk\Model\PodcastEpisode;
use eLife\Search\Api\ApiValidator;
use eLife\Search\Api\Elasticsearch\MappedElasticsearchClient;
use eLife\Search\Workflow\PodcastEpisodeWorkflow;
use eLife\Search\Workflow\Workflow;
use Psr\Log\LoggerInterface;
use Symfony\Component\Serializer\Serializer;

final class PodcastEpisodeWorkflowTest extends WorkflowTestCase
{
    protected function setWorkflow(
        Serializer $serializer,
        LoggerInterface $logger,
        MappedElasticsearchClient $client,
        ApiValidator $validator
    ) : Workflow
    {
        return new PodcastEpisodeWorkflow($serializer, $logger, $client, $validator);
    }

    protected function getModel() : string
    {
        return 'podcast-episode';
    }

    protected function getModelClass() : string
    {
        return PodcastEpisode::class;
    }

    protected function getVersion() : int
    {
        return 1;
    }

    /**
     * @dataProvider workflowProvider
     * @test
     */
    public function testSerializationSmokeTest(PodcastEpisode $podcastEpisode)
    {
        // Mock the HTTP call that's made for subjects.
        $this->mockSubjects();
        // Check A to B
        $serialized = $this->workflow->serialize($podcastEpisode);
        /** @var PodcastEpisode $deserialized */
        $deserialized = $this->workflow->deserialize($serialized);
        $this->assertInstanceOf(PodcastEpisode::class, $deserialized);
        // Check B to A
        $final_serialized = $this->workflow->serialize($deserialized);
        $this->assertJsonStringEqualsJsonString($serialized, $final_serialized);
    }

    /**
     * @dataProvider workflowProvider
     * @test
     */
    public function testIndexOfPodcastEpisode(PodcastEpisode $podcastEpisode)
    {
        $return = $this->workflow->index($podcastEpisode);
        $article = $return['json'];
        $id = $return['id'];
        $this->assertJson($article, 'PodcastEpisode is not valid JSON');
        $this->assertNotNull($id, 'An ID is required.');
        $this->assertStringStartsWith('podcast-episode-', $id, 'ID should be assigned an appropriate prefix.');
    }

    /**
     * @dataProvider workflowProvider
     * @test
     */
    public function testInsertOfPodcastEpisode(PodcastEpisode $podcastEpisode)
    {
        $this->elastic->shouldReceive('indexJsonDocument');
        $ret = $this->workflow->insert($this->workflow->serialize($podcastEpisode), $podcastEpisode->getNumber());
        $this->assertArrayHasKey('id', $ret);
        $id = $ret['id'];
        $this->assertEquals($podcastEpisode->getNumber(), $id);
    }
}
