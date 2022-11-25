<?php

namespace tests\eLife\Search\Workflow;

use eLife\ApiClient\ApiClient\PodcastClient;
use eLife\ApiSdk\ApiSdk;
use eLife\ApiSdk\Mode\FundingAward;
use eLife\ApiSdk\Model\PodcastEpisode;
use eLife\ApiSdk\Serializer\PodcastEpisodeNormalizer;
use eLife\Search\Api\Elasticsearch\MappedElasticsearchClient;
use eLife\Search\Workflow\PodcastEpisodeWorkflow;
use Mockery;
use tests\eLife\Search\ExceptionNullLogger;

class PodcastEpisodeWorkflowTest extends WorkflowTestCase
{
    /**
     * @var PodcastEpisodeWorkflow
     */
    private $workflow;
    private $elastic;
    private $validator;

    public function setUp()
    {
        $this->elastic = Mockery::mock(MappedElasticsearchClient::class);
        $logger = new ExceptionNullLogger();
        $this->validator = $this->getValidator();
        $this->workflow = new PodcastEpisodeWorkflow($this->getSerializer(), $logger, $this->elastic, $this->validator);
    }

    protected function setUpSerializer()
    {
        $apiSdk = new ApiSdk($this->getHttpClient());
        $this->denormalizer = new PodcastEpisodeNormalizer(new PodcastClient($this->getHttpClient()));
        $this->denormalizer->setNormalizer($apiSdk->getSerializer());
        $this->denormalizer->setDenormalizer($apiSdk->getSerializer());
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
    public function testSerializationSmokeTest(PodcastEpisode $podcastEpisode, array $context = [], array $expected = [])
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
