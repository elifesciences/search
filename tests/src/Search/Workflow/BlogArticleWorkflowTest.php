<?php

namespace tests\eLife\Search\Workflow;

use DateTimeImmutable;
use eLife\ApiSdk\Collection\ArrayCollection;
use eLife\ApiSdk\Model\Block\Paragraph;
use eLife\ApiSdk\Model\BlogArticle;
use eLife\ApiSdk\Model\Image;
use eLife\ApiSdk\Model\ImageSize;
use eLife\ApiSdk\Model\Subject;
use eLife\Search\Workflow\BlogArticleWorkflow;
use PHPUnit_Framework_TestCase;
use tests\eLife\Search\AsyncAssert;
use tests\eLife\Search\HttpMocks;

class BlogArticleWorkflowTest extends PHPUnit_Framework_TestCase
{
    use AsyncAssert;
    use HttpMocks;
    use GetSerializer;

    /**
     * @var BlogArticleWorkflow
     */
    private $workflow;

    public function setUp()
    {
        $this->workflow = new BlogArticleWorkflow($this->getSerializer());
    }

    /**
     * @dataProvider blogArticleProvider
     * @test
     */
    public function testSerializationSmokeTest(BlogArticle $blogArticle)
    {
        // Mock the HTTP call that's made for subjects.
        $this->mockSubjects();

        // Check A to B
        $serialized = $this->workflow->serializeArticle($blogArticle);
        $deserialized = $this->workflow->deserializeArticle($serialized);

        $this->asyncAssertEquals($blogArticle->getContent(), $deserialized->getContent(), 'Content matches after serializing');
        $this->asyncAssertEquals($blogArticle->getId(), $deserialized->getId(), 'Id matches after serializing');
        $this->asyncAssertEquals($blogArticle->getImpactStatement(), $deserialized->getImpactStatement(), 'Impact statement matches after serializing');
        $this->asyncAssertEquals($blogArticle->getPublishedDate(), $deserialized->getPublishedDate(), 'Published date matches after serializing');
        $this->asyncAssertEquals($blogArticle->getSubjects(), $deserialized->getSubjects(), 'Subjects matches after serializing');
        $this->asyncAssertEquals($blogArticle->getTitle(), $deserialized->getTitle(), 'Title matches after serializing');

        // Check B to A
        $final_serialized = $this->workflow->serializeArticle($deserialized);
        $this->assertJsonStringEqualsJsonString($serialized, $final_serialized);
    }

    /**
     * @dataProvider blogArticleProvider
     * @test
     */
    public function testValidationOfBlogArticle(BlogArticle $blogArticle)
    {
        $return = $this->workflow->validate($blogArticle);
        $this->assertInstanceOf(BlogArticle::class, $return);
    }

    /**
     * @dataProvider blogArticleProvider
     * @test
     */
    public function testIndexOfBlogArticle(BlogArticle $blogArticle)
    {
        $return = $this->workflow->index($blogArticle);
        $article = $return['json'];
        $index = $return['index'];
        $this->assertJson($article, 'Article is not valid JSON');
        $this->assertNotNull($index, 'An index is required.');
    }

    /**
     * @dataProvider blogArticleProvider
     * @test
     */
    public function testInsertOfBlogArticle(BlogArticle $blogArticle)
    {
        $return = $this->workflow->insert($this->workflow->serializeArticle($blogArticle), []);
        $this->assertEquals($return, BlogArticleWorkflow::WORKFLOW_SUCCESS, 'Workflow must return success message.');
    }

    public function blogArticleProvider() : array
    {
        $date = new DateTimeImmutable();
        $image = new Image('', [new ImageSize('2:1', [900 => 'https://placehold.it/900x450'])]);
        $subject = new Subject('id', 'name', null, $image);

        return [
            'complete' => [
                new BlogArticle('id', 'title', $date, 'impact statement', new ArrayCollection([new Paragraph('text')]),
                    new ArrayCollection([$subject])),
            ],
            'minimum' => [
                new BlogArticle('id', 'title', $date, null, new ArrayCollection([new Paragraph('text')]), null),
            ],
            'complete snippet' => [
                new BlogArticle('id', 'title', $date, 'impact statement',
                    new ArrayCollection([new Paragraph('text')]),
                    new ArrayCollection([$subject])),
            ],
            'minimum snippet' => [
                new BlogArticle('id', 'title', $date, null,
                    new ArrayCollection([new Paragraph('text')]), null),
            ],
        ];
    }
}
