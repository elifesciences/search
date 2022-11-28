<?php

namespace tests\eLife\Search\Workflow;

use DateTimeImmutable;
use eLife\ApiSdk\Collection\EmptySequence;
use eLife\ApiSdk\Model\ArticlePoA;
use eLife\ApiSdk\Model\ArticleVersion;
use eLife\ApiSdk\Model\ArticleVoR;
use eLife\ApiSdk\Model\Copyright;
use eLife\Search\Api\ApiValidator;
use eLife\Search\Api\Elasticsearch\MappedElasticsearchClient;
use eLife\Search\Workflow\ResearchArticleWorkflow;
use eLife\Search\Workflow\Workflow;
use Psr\Log\LoggerInterface;
use Symfony\Component\Serializer\Serializer;
use tests\eLife\Search\ExceptionNullLogger;
use Traversable;
use function GuzzleHttp\Promise\promise_for;

final class ResearchArticleWorkflowTest extends WorkflowTestCase
{
    protected function setWorkflow(
        Serializer $serializer,
        LoggerInterface $logger,
        MappedElasticsearchClient $client,
        ApiValidator $validator
    ) : Workflow
    {
        return new ResearchArticleWorkflow($serializer, $logger, $client, $validator);
    }

    /**
     * @dataProvider workflowProvider
     * @test
     */
    public function testSerializationSmokeTest(ArticleVersion $researchArticle)
    {
        // Mock the HTTP call that's made for subjects.
        $this->mockSubjects();
        // Check A to B
        $serialized = $this->workflow->serialize($researchArticle);
        /** @var ArticlePoA $deserialized */
        $deserialized = $this->workflow->deserialize($serialized);
        $this->assertInstanceOf(ArticleVersion::class, $deserialized);
        // Check B to A
        $final_serialized = $this->workflow->serialize($deserialized);
        $this->assertJsonStringEqualsJsonString($serialized, $final_serialized);
    }

    /**
     * @dataProvider workflowProvider
     * @test
     */
    public function testIndexOfResearchArticle(ArticleVersion $researchArticle)
    {
        $return = $this->workflow->index($researchArticle);
        $article = $return['json'];
        $id = $return['id'];
        $this->assertJson($article, 'Article is not valid JSON');
        $this->assertNotNull($id, 'An ID is required.');
        $this->assertStringStartsWith('research-article-', $id, 'ID should be assigned an appropriate prefix.');
    }

    public function testStatusDateIsUsedAsTheSortDateWhenThereIsNoRdsArticle()
    {
        $this->workflow = new ResearchArticleWorkflow($this->getSerializer(), new ExceptionNullLogger(),
            $this->elastic, $this->validator, ['article-2' => ['date' => '2020-09-08T07:06:05Z']]);

        $article = $this->getArticle('article-1');

        $return = json_decode($this->workflow->index($article)['json'], true);

        $this->assertSame('2010-02-03T04:05:06Z', $return['sortDate']);
    }

    public function testRdsDateIsUsedAsTheSortDateWhenThereIsAnRdsArticle()
    {
        $this->workflow = new ResearchArticleWorkflow($this->getSerializer(), new ExceptionNullLogger(),
            $this->elastic, $this->validator, ['article-2' => ['date' => '2020-09-08T07:06:05Z']]);

        $article = $this->getArticle();

        $return = json_decode($this->workflow->index($article)['json'], true);

        $this->assertSame('2020-09-08T07:06:05Z', $return['sortDate']);
    }

    public function testReviewedDateAndCurationLabelsWhenThereIsAReviewedPreprint()
    {
        $this->workflow = new ResearchArticleWorkflow($this->getSerializer(), new ExceptionNullLogger(),
            $this->elastic, $this->validator, [], ['article-2' => ['reviewedDate' => '2020-09-08T07:06:05Z', 'curationLabels' => ['foo', 'bar']]]);

        $this->elastic->shouldReceive('deleteDocument');
        $article = $this->getArticle();

        $return = json_decode($this->workflow->index($article)['json'], true);

        $snippet = json_decode($return['snippet']['value'], true);
        $this->assertSame('2020-09-08T07:06:05Z', $snippet['reviewedDate']);
        $this->assertSame(['foo', 'bar'], $snippet['curationLabels']);
    }

    /**
     * @dataProvider workflowProvider
     * @test
     */
    public function testInsertOfResearchArticle(ArticleVersion $researchArticle)
    {
        // TODO: this should set up an expectation about actual ArticlePoA data being received, as passing in a BlogArticle doesn't break the test
        $this->elastic->shouldReceive('indexJsonDocument');
        $ret = $this->workflow->insert($this->workflow->serialize($researchArticle), $researchArticle->getId());
        $this->assertArrayHasKey('id', $ret);
        $id = $ret['id'];
        $this->assertEquals($researchArticle->getId(), $id);
    }

    public function workflowProvider(string $model = null, string $modelClass = null, int $version = null) : Traversable
    {
        foreach (array_merge(
            iterator_to_array(parent::workflowProvider('article-vor', ArticleVoR::class, 6)),
            iterator_to_array(parent::workflowProvider('article-poa', ArticlePoA::class, 3))
        ) as $k => $v) {
            yield $k => $v;
        }
    }

    private function getArticle($id = 'article-2')
    {
        return new ArticlePoA(
            $id,
            'published',
            4,
            'research-article',
            'DOI',
            null, null, 'title', null, null,
            new DateTimeImmutable('2010-02-03T04:05:06Z'),
            1, 'elocationId', null, null, null,
            promise_for(''),
            new EmptySequence(), [], null, promise_for(''),
            promise_for(new Copyright('', '')),
            new EmptySequence(), new EmptySequence(), new EmptySequence(), promise_for(''),
            new EmptySequence(), new EmptySequence(), new EmptySequence(), new EmptySequence()
        );
    }
}
