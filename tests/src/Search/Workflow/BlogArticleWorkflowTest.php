<?php

namespace tests\eLife\Search\Workflow;

use eLife\ApiSdk\Model\BlogArticle;
use eLife\Search\Api\Elasticsearch\ElasticsearchClient;
use eLife\Search\Workflow\BlogArticleWorkflow;
use Mockery;
use Mockery\Mock;
use PHPUnit_Framework_TestCase;
use test\eLife\ApiSdk\Serializer\BlogArticleNormalizerTest;
use tests\eLife\Search\AsyncAssert;
use tests\eLife\Search\ExceptionNullLogger;
use tests\eLife\Search\HttpMocks;

class BlogArticleWorkflowTest extends PHPUnit_Framework_TestCase
{
    use AsyncAssert;
    use HttpMocks;
    use GetSerializer;
    use GetValidator;

    /**
     * @var BlogArticleWorkflow
     */
    private $workflow;
    private $elastic;
    private $validator;

    public function setUp()
    {
        $this->elastic = Mockery::mock(ElasticsearchClient::class);

        $logger = new ExceptionNullLogger();
        $this->validator = $this->getValidator();
        $this->workflow = new BlogArticleWorkflow($this->getSerializer(), $logger, $this->elastic, $this->validator);
    }

    public function tearDown()
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
        $this->markTestIncomplete('Work in progress');
        // Mock the HTTP call that's made for subjects.
        $this->mockSubjects();

        // Check A to B
        $serialized = $this->workflow->serialize($blogArticle);
        /** @var BlogArticle $deserialized */
        $deserialized = $this->workflow->deserialize($serialized);

        $this->assertInstanceOf(BlogArticle::class, $deserialized);
        $this->asyncAssertEquals($blogArticle->getContent(), $deserialized->getContent(), 'Content matches after serializing');
        $this->asyncAssertEquals($blogArticle->getId(), $deserialized->getId(), 'Id matches after serializing');
        $this->asyncAssertEquals($blogArticle->getImpactStatement(), $deserialized->getImpactStatement(), 'Impact statement matches after serializing');
        $this->asyncAssertEquals($blogArticle->getPublishedDate(), $deserialized->getPublishedDate(), 'Published date matches after serializing');
        $this->asyncAssertEquals($blogArticle->getSubjects(), $deserialized->getSubjects(), 'Subjects matches after serializing');
        $this->asyncAssertEquals($blogArticle->getTitle(), $deserialized->getTitle(), 'Title matches after serializing');

        // Check B to A
        $final_serialized = $this->workflow->serialize($deserialized);
        $this->assertJsonStringEqualsJsonString($serialized, $final_serialized);
    }

    /**
     * @dataProvider blogArticleProvider
     * @test
     */
    public function testValidationOfBlogArticle(BlogArticle $blogArticle)
    {
        // Validator is being inaccurate.
        // $return = $this->workflow->validate($blogArticle);
        // $this->assertInstanceOf(BlogArticle::class, $return);
    }

    /**
     * @dataProvider blogArticleProvider
     * @test
     */
    public function testIndexOfBlogArticle(BlogArticle $blogArticle)
    {
        $return = $this->workflow->index($blogArticle);
        $article = $return['json'];
        $type = $return['type'];
        $id = $return['id'];
        $this->assertJson($article, 'Article is not valid JSON');
        $this->assertEquals('blog-article', $type, 'A type is required.');
        $this->assertNotNull($id, 'An ID is required.');
    }

    /**
     * @dataProvider blogArticleProvider
     * @test
     */
    public function testInsertOfBlogArticle(BlogArticle $blogArticle)
    {
        $this->elastic->shouldReceive('indexJsonDocument');
        $ret = $this->workflow->insert($this->workflow->serialize($blogArticle), 'blog-article', $blogArticle->getId());
        $this->assertArrayHasKey('type', $ret);
        $this->assertArrayHasKey('id', $ret);
        $id = $ret['id'];
        $type = $ret['type'];
        $this->assertEquals('blog-article', $type);
        $this->assertEquals($blogArticle->getId(), $id);
    }

    public function blogArticleProvider() : array
    {
        return (new BlogArticleNormalizerTest())->normalizeProvider();
    }
}
