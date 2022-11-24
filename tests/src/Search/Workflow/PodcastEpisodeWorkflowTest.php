<?php

namespace tests\eLife\Search\Workflow;

use DateTimeImmutable;
use DateTimeZone;
use eLife\ApiSdk\Collection\ArraySequence;
use eLife\ApiSdk\Collection\EmptySequence;
use eLife\ApiSdk\Model\ArticlePoA;
use eLife\ApiSdk\Model\File;
use eLife\ApiSdk\Model\Funder;
use eLife\ApiSdk\Model\Funding;
use eLife\ApiSdk\Model\FundingAward;
use eLife\ApiSdk\Model\Image;
use eLife\ApiSdk\Model\PersonAuthor;
use eLife\ApiSdk\Model\PersonDetails;
use eLife\ApiSdk\Model\Place;
use eLife\ApiSdk\Model\PodcastEpisode;
use eLife\ApiSdk\Model\PodcastEpisodeChapter;
use eLife\ApiSdk\Model\PodcastEpisodeSource;
use eLife\ApiSdk\Model\Subject;
use eLife\Search\Api\Elasticsearch\MappedElasticsearchClient;
use eLife\Search\Workflow\PodcastEpisodeWorkflow;
use Mockery;
use PHPUnit_Framework_TestCase;
use tests\eLife\Search\AsyncAssert;
use tests\eLife\Search\ExceptionNullLogger;
use tests\eLife\Search\HttpMocks;
use function GuzzleHttp\Promise\promise_for;

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
     * @dataProvider podcastEpisodeProvider
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

    public function podcastEpisodeProvider() : array
    {
        $published = new DateTimeImmutable('yesterday', new DateTimeZone('Z'));
        $updated = new DateTimeImmutable('now', new DateTimeZone('Z'));
        $banner = new Image('', 'https://iiif.elifesciences.org/banner.jpg',
            new EmptySequence(),
            new File('image/jpeg', 'https://iiif.elifesciences.org/banner.jpg/full/full/0/default.jpg', 'banner.jpg'),
            1800, 900, 50, 50);

        $thumbnail = new Image('', 'https://iiif.elifesciences.org/banner.jpg',
            new EmptySequence(),
            new File('image/jpeg', 'https://iiif.elifesciences.org/banner.jpg/full/full/0/default.jpg', 'banner.jpg'),
            1800, 900, 50, 50);

        $socialImage = new Image(
            '',
            'https://iiif.elifesciences.org/banner.jpg',
            new EmptySequence(),
            new File('image/jpeg', 'https://iiif.elifesciences.org/banner.jpg/full/full/0/default.jpg', 'banner.jpg'),
            1800, 900, 50, 50);

        $subject = new Subject('subject1', 'Subject 1 name', promise_for('Subject subject1 impact statement'),
            new EmptySequence(), promise_for($banner), promise_for($thumbnail), promise_for($socialImage));

        return [
            'complete' => [
                new PodcastEpisode(1, 'Podcast episode 1 title',
                    'Podcast episode 1 impact statement',
                    $published,
                    $updated,
                    promise_for($banner), $thumbnail, promise_for($socialImage),
                    [new PodcastEpisodeSource('audio/mpeg', 'https://www.example.com/episode.mp3')],
                    new ArraySequence([
                        new PodcastEpisodeChapter(1,
                            'Chapter title',
                            'Long chapter title',
                            0,
                            'Chapter impact statement',
                            new ArraySequence([
                                new ArticlePoA('id', 'published', 4, 'research-article', '',
                                    null, 'title prefix', 'title', $published, null, null, 4,
                                    'elocatoin', null, null, 'http:',
                                    promise_for(''),
                                    new ArraySequence([$subject]), [''], null, promise_for(1), promise_for('copyright'),
                                    new EmptySequence(), new EmptySequence(), new EmptySequence(), promise_for(
                                        new Funding(
                                            new ArraySequence([
                                                new FundingAward(
                                                    'award',
                                                    new Funder(new Place(['Funder']), '10.13039/501100001659'),
                                                    'awardId',
                                                    new ArraySequence([new PersonAuthor(
                                                        new PersonDetails('Author', 'Author'))]
                                                    )
                                                ),
                                            ]),
                                        'Funding statement'
                                    )),
                                    new EmptySequence(),
                                    new EmptySequence(),
                                    new EmptySequence(),
                                    new EmptySequence()
                                ),
                            ])
                        ),
                    ])),
            ],
            'minimum' => [
                new PodcastEpisode(
                    1,
                    'Podcast episode 1 title',
                    null,
                    $published,
                    null,
                    promise_for($banner),
                    $thumbnail,
                    promise_for(null),
                    [
                        new PodcastEpisodeSource(
                            'audio/mpeg',
                            'https://www.example.com/episode.mp3'
                        ),
                    ],
                    new ArraySequence([
                        new PodcastEpisodeChapter(
                            1,
                            'Chapter title',
                            null,
                            0,
                            null,
                            new EmptySequence()),
                    ])
                ),
            ],
        ];
    }
}
