<?php

namespace tests\eLife\Search\Workflow;

use ComposerLocator;
use DateTimeImmutable;
use DateTimeZone;
use eLife\ApiSdk\Collection\ArraySequence;
use eLife\ApiSdk\Collection\EmptySequence;
use eLife\ApiSdk\Model\Annotation;
use eLife\ApiSdk\Model\AnnualReport;
use eLife\ApiSdk\Model\ArticlePoA;
use eLife\ApiSdk\Model\ArticleVoR;
use eLife\ApiSdk\Model\Block\Paragraph;
use eLife\ApiSdk\Model\BlogArticle;
use eLife\ApiSdk\Model\Collection;
use eLife\ApiSdk\Model\Cover;
use eLife\ApiSdk\Model\Digest;
use eLife\ApiSdk\Model\Event;
use eLife\ApiSdk\Model\ExternalArticle;
use eLife\ApiSdk\Model\File;
use eLife\ApiSdk\Model\Highlight;
use eLife\ApiSdk\Model\Image;
use eLife\ApiSdk\Model\Interview;
use eLife\ApiSdk\Model\JobAdvert;
use eLife\ApiSdk\Model\LabsPost;
use eLife\ApiSdk\Model\Person;
use eLife\ApiSdk\Model\PodcastEpisode;

use eLife\ApiSdk\Model\PodcastEpisodeChapterModel;
use eLife\ApiSdk\Model\PressPackage;
use eLife\ApiSdk\Model\Profile;
use eLife\ApiSdk\Model\PromotionalCollection;
use eLife\ApiSdk\Model\ReviewedPreprint;
use eLife\ApiSdk\Model\Subject;
use eLife\Search\Api\Elasticsearch\MappedElasticsearchClient;
use eLife\Search\Workflow\BlogArticleWorkflow;
use Mockery;
use Mockery\Mock;
use PHPUnit_Framework_TestCase;
use Symfony\Component\Finder\Finder;
use test\eLife\ApiSdk\Builder;
use test\eLife\ApiSdk\Serializer\BlogArticleNormalizerTest;
use tests\eLife\Search\AsyncAssert;
use tests\eLife\Search\ExceptionNullLogger;
use tests\eLife\Search\HttpMocks;
use function GuzzleHttp\json_decode;
use function GuzzleHttp\Promise\promise_for;

class BlogArticleWorkflowTest extends PHPUnit_Framework_TestCase
{
    use AsyncAssert;
    use HttpMocks;
    use GetSerializer;
    use GetValidator;
    private static $classes = [
        'annotation' => Annotation::class,
        'annual-report' => AnnualReport::class,
        'article-poa' =>  ArticlePoA::class,
        'article-vor' => ArticleVoR::class,
        'blog-article' => BlogArticle::class,
        'collection' => Collection::class,
        'cover' => Cover::class,
        'digest' => Digest::class,
        'event' => Event::class,
        'external-article' => ExternalArticle::class,
        'highlight' => Highlight::class,
        'interview' => Interview::class,
        'job-advert' => JobAdvert::class,
        'labs-post' => LabsPost::class,
        'person' => Person::class,
        'podcast-episode' => PodcastEpisode::class,
        'podcast-episode-chapter' => PodcastEpisodeChapterModel::class,
        'press-package' => PressPackage::class,
        'profile' => Profile::class,
        'promotional-collection' => PromotionalCollection::class,
        'reviewed-preprint' => ReviewedPreprint::class,
        'subject' => Subject::class,
    ];
    /**
     * @var BlogArticleWorkflow
     */
    private $workflow;
    private $elastic;
    private $validator;

    public function setUp()
    {
        $this->elastic = Mockery::mock(MappedElasticsearchClient::class);

        $logger = new ExceptionNullLogger();
        $this->validator = $this->getValidator();
        $this->workflow = new BlogArticleWorkflow($this->getSerializer(), $logger, $this->elastic, $this->validator);
    }

    public function asyncTearDown()
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * @dataProvider blogArticleProvider
     * @test
     */
    public function testSerializationSmokeTest(BlogArticle $blogArticle, array $context = [], array $expected = [])
    {
        // Mock the HTTP call that's made for subjects.
        $this->mockSubjects();
        // Check A to B
        $serialized = $this->workflow->serialize($blogArticle);
        /** @var BlogArticle $deserialized */
        $deserialized = $this->workflow->deserialize($serialized);
        $this->assertInstanceOf(BlogArticle::class, $deserialized);
        // Check B to A
        $final_serialized = $this->workflow->serialize($deserialized);
        $this->assertJsonStringEqualsJsonString($serialized, $final_serialized);
    }

    /**
     * @dataProvider blogArticleProvider
     * @test
     */
    public function testIndexOfBlogArticle(BlogArticle $blogArticle)
    {
        $return = $this->workflow->index($blogArticle);
        $article = $return['json'];
        $id = $return['id'];
        $this->assertJson($article, 'Article is not valid JSON');
        $this->assertNotNull($id, 'An ID is required.');
        $this->assertStringStartsWith('blog-article-', $id, 'ID should be assigned an appropriate prefix.');
    }

    /**
     * @dataProvider blogArticleProvider
     * @test
     */
    public function testInsertOfBlogArticle(BlogArticle $blogArticle)
    {
        $this->elastic->shouldReceive('indexJsonDocument');
        $ret = $this->workflow->insert($this->workflow->serialize($blogArticle), $blogArticle->getId());
        $this->assertArrayHasKey('id', $ret);
        $id = $ret['id'];
        $this->assertEquals($blogArticle->getId(), $id);
    }

    public function blogArticleProvider()
    {
       // return (new BlogArticleNormalizerTest())->normalizeProvider();
        $model = 'blog-article';
        $version = 2;

        $samples = Finder::create()->files()->in(ComposerLocator::getPath('elife/api')."/dist/samples/{$model}/v{$version}");
        $date = new DateTimeImmutable('yesterday', new DateTimeZone('Z'));
        $updatedDate = new DateTimeImmutable('now', new DateTimeZone('Z'));
        $banner = new Image('', 'https://iiif.elifesciences.org/banner.jpg', new EmptySequence(), new File('image/jpeg', 'https://iiif.elifesciences.org/banner.jpg/full/full/0/default.jpg', 'banner.jpg'), 1800, 900, 50, 50);
        $thumbnail = new Image('', 'https://iiif.elifesciences.org/banner.jpg', new EmptySequence(), new File('image/jpeg', 'https://iiif.elifesciences.org/banner.jpg/full/full/0/default.jpg', 'banner.jpg'), 1800, 900, 50, 50);
        $socialImage = new Image('', 'https://iiif.elifesciences.org/banner.jpg', new EmptySequence(), new File('image/jpeg', 'https://iiif.elifesciences.org/banner.jpg/full/full/0/default.jpg', 'banner.jpg'), 1800, 900, 50, 50);

        $subject = new Subject('subject1', 'Subject 1 name', promise_for('Subject subject1 impact statement'),
            new EmptySequence(), promise_for($banner), promise_for($thumbnail), promise_for($socialImage));

        foreach ($samples as $sample) {
            $name = "{$model}/v{$version}/{$sample->getBasename()}";
            $contents = json_decode($sample->getContents(), true);
            yield $name => [
                new BlogArticle('id', 'title', $date, $updatedDate, 'impact statement', promise_for(Builder::for(Image::class)->sample('social')), new ArraySequence([new Paragraph('text')]),
                    new ArraySequence([$subject])),
                [],
                $contents
            ];
        }
    }
}
