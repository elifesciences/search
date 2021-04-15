<?php

namespace tests\eLife\Search\Workflow;

use DateTimeImmutable;
use eLife\ApiSdk\Model\ArticlePoA;
use eLife\Search\Api\Elasticsearch\MappedElasticsearchClient;
use eLife\Search\Workflow\ResearchArticleWorkflow;
use Mockery;
use Mockery\Mock;
use PHPUnit_Framework_TestCase;
use test\eLife\ApiSdk\Builder;
use test\eLife\ApiSdk\Serializer\ArticlePoANormalizerTest;
use tests\eLife\Search\AsyncAssert;
use tests\eLife\Search\ExceptionNullLogger;
use tests\eLife\Search\HttpMocks;

class ResearchArticleWorkflowTest extends PHPUnit_Framework_TestCase
{
    use AsyncAssert;
    use HttpMocks;
    use GetSerializer;
    use GetValidator;

    /**
     * @var ResearchArticleWorkflow
     */
    private $workflow;
    private $elastic;
    private $validator;

    public function setUp()
    {
        $this->elastic = Mockery::mock(MappedElasticsearchClient::class);

        $logger = new ExceptionNullLogger();
        $this->validator = $this->getValidator();
        $this->workflow = new ResearchArticleWorkflow($this->getSerializer(), $logger, $this->elastic, $this->validator);
    }

    public function asyncTearDown()
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * @dataProvider researchArticleProvider
     * @test
     */
    public function testSerializationSmokeTest(ArticlePoA $researchArticle, array $context = [], array $expected = [])
    {
        // Mock the HTTP call that's made for subjects.
        $this->mockSubjects();
        // Check A to B
        $serialized = $this->workflow->serialize($researchArticle);
        /** @var ArticlePoA $deserialized */
        $deserialized = $this->workflow->deserialize($serialized);
        $this->assertInstanceOf(ArticlePoA::class, $deserialized);
        // Check B to A
        $final_serialized = $this->workflow->serialize($deserialized);
        $this->assertJsonStringEqualsJsonString($serialized, $final_serialized);
    }

    /**
     * @dataProvider researchArticleProvider
     * @test
     */
    public function testIndexOfResearchArticle(ArticlePoA $researchArticle)
    {
        $return = $this->workflow->index($researchArticle);
        $article = $return['json'];
        $type = $return['type'];
        $id = $return['id'];
        $this->assertJson($article, 'Article is not valid JSON');
        $this->assertEquals('research-article', $type, 'A type is required.');
        $this->assertNotNull($id, 'An ID is required.');
    }

    public function testStatusDateIsUsedAsTheSortDateWhenThereIsNoRdsArticle()
    {
        $this->workflow = new ResearchArticleWorkflow($this->getSerializer(), new ExceptionNullLogger(),
            $this->elastic, $this->validator, ['article-2' => ['date' => '2020-09-08T07:06:05Z']]);

        $article = Builder::for(ArticlePoA::class)
            ->withId('article-1')
            ->withStatusDate(new DateTimeImmutable('2010-02-03T04:05:06Z'))
            ->__invoke();

        $return = json_decode($this->workflow->index($article)['json'], true);

        $this->assertSame('2010-02-03T04:05:06Z', $return['sortDate']);
    }

    public function testRdsDateIsUsedAsTheSortDateWhenThereIsAnRdsArticle()
    {
        $this->workflow = new ResearchArticleWorkflow($this->getSerializer(), new ExceptionNullLogger(),
            $this->elastic, $this->validator, ['article-2' => ['date' => '2020-09-08T07:06:05Z']]);

        $article = Builder::for(ArticlePoA::class)
            ->withId('article-2')
            ->withStatusDate(new DateTimeImmutable('2010-02-03T04:05:06Z'))
            ->__invoke();

        $return = json_decode($this->workflow->index($article)['json'], true);

        $this->assertSame('2020-09-08T07:06:05Z', $return['sortDate']);
    }

    /**
     * @dataProvider researchArticleProvider
     * @test
     */
    public function testInsertOfResearchArticle(ArticlePoA $researchArticle)
    {
        // TODO: this should set up an expectation about actual ArticlePoA data being received, as passing in a BlogArticle doesn't break the test
        $this->elastic->shouldReceive('indexJsonDocument');
        $ret = $this->workflow->insert($this->workflow->serialize($researchArticle), 'research-article', $researchArticle->getId());
        $this->assertArrayHasKey('type', $ret);
        $this->assertArrayHasKey('id', $ret);
        $id = $ret['id'];
        $type = $ret['type'];
        $this->assertEquals('research-article', $type);
        $this->assertEquals($researchArticle->getId(), $id);
    }

    public function researchArticleProvider() : array
    {
        return (new ArticlePoANormalizerTest())->normalizeProvider();
    }
}
