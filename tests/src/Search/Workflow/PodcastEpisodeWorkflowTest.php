<?php

namespace tests\eLife\Search\Workflow;

use eLife\ApiSdk\Model\PodcastEpisode;
use eLife\Search\Api\Elasticsearch\MappedElasticsearchClient;
use eLife\Search\Workflow\PodcastEpisodeWorkflow;
use Mockery;
use PHPUnit_Framework_TestCase;
use test\eLife\ApiSdk\Serializer\PodcastEpisodeNormalizerTest;
use tests\eLife\Search\AsyncAssert;
use tests\eLife\Search\ExceptionNullLogger;
use tests\eLife\Search\HttpMocks;

class PodcastEpisodeWorkflowTest extends PHPUnit_Framework_TestCase
{
    use AsyncAssert;
    use HttpMocks;
    use GetSerializer;
    use GetValidator;

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

    public function asyncTearDown()
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * @dataProvider podcastEpisodeProvider
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
     * @dataProvider podcastEpisodeProvider
     * @test
     */
    public function testValidationOfPodcastEpisode(PodcastEpisode $podcastEpisode)
    {
        $return = $this->workflow->validate($podcastEpisode);
        $this->assertInstanceOf(PodcastEpisode::class, $return);
    }

    /**
     * @dataProvider podcastEpisodeProvider
     * @test
     */
    public function testIndexOfPodcastEpisode(PodcastEpisode $podcastEpisode)
    {
        $return = $this->workflow->index($podcastEpisode);
        $article = $return['json'];
        $type = $return['type'];
        $id = $return['id'];
        $this->assertJson($article, 'PodcastEpisode is not valid JSON');
        $this->assertEquals('podcast-episode', $type, 'A type is required.');
        $this->assertNotNull($id, 'An ID is required.');
    }

    /**
     * @dataProvider podcastEpisodeProvider
     * @test
     */
    public function testInsertOfPodcastEpisode(PodcastEpisode $podcastEpisode)
    {
        $this->elastic->shouldReceive('indexJsonDocument');
        $ret = $this->workflow->insert($this->workflow->serialize($podcastEpisode), 'podcast-episode', $podcastEpisode->getNumber());
        $this->assertArrayHasKey('type', $ret);
        $this->assertArrayHasKey('id', $ret);
        $id = $ret['id'];
        $type = $ret['type'];
        $this->assertEquals('podcast-episode', $type);
        $this->assertEquals($podcastEpisode->getNumber(), $id);
    }

    public function podcastEpisodeProvider() : array
    {
        return (new PodcastEpisodeNormalizerTest())->normalizeProvider();
    }
}
