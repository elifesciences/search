<?php

namespace tests\eLife\Search\Indexer\ModelIndexer;

use eLife\ApiSdk\Client\Articles;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Mockery;
use eLife\ApiSdk\Model\ArticleVoR;
use eLife\ApiSdk\Model\ArticlePoA;
use eLife\ApiSdk\Model\ArticleVersion;
use eLife\Search\Api\Elasticsearch\MappedElasticsearchClient;
use eLife\Search\Indexer\ModelIndexer\ArticleIndexer;
use Mockery\MockInterface;
use Traversable;

final class ArticleIndexerTest extends TestCase
{
    use GetSerializer;
    use CallSerializer;
    use ModelProvider;

    private ArticleIndexer $indexer;

    protected function setUp(): void
    {
        $this->indexer = new ArticleIndexer($this->getSerializer(), []);
    }

    protected static function getModelDefinitions(): array
    {
        return [
            ['model' => 'article-vor', 'modelClass' => ArticleVoR::class, 'version' => Articles::VERSION_ARTICLE_VOR],
            ['model' => 'article-poa', 'modelClass' => ArticlePoA::class, 'version' => Articles::VERSION_ARTICLE_POA],
        ];
    }

    #[DataProvider('modelProvider')]
    #[Test]
    public function testSerializationSmokeTest(ArticleVersion $articleVersion)
    {
        // Check A to B
        $serialized = $this->callSerialize($this->indexer, $articleVersion);
        /** @var ArticlePoA $deserialized */
        $deserialized = $this->callDeserialize($this->indexer, $serialized);
        $this->assertInstanceOf(ArticleVersion::class, $deserialized);
        // Check B to A
        $final_serialized = $this->callSerialize($this->indexer, $deserialized);
        $this->assertJsonStringEqualsJsonString($serialized, $final_serialized);
    }

    #[DataProvider('modelProvider')]
    #[Test]
    public function testIndexOfArticle(ArticleVersion $articleVersion)
    {
        $changeSet = $this->indexer->prepareChangeSet($articleVersion);

        $this->assertCount(1, $changeSet->getInserts());

        $insert = $changeSet->getInserts()[0];
        $articleJson = $insert['json'];
        $id = $insert['id'];
        $this->assertJson($articleJson, 'Article is not valid JSON');
        $this->assertNotNull($id, 'An ID is required.');
        $this->assertStringStartsWith('research-article-', $id, 'ID should be assigned an appropriate prefix.');

        if ($articleVersion instanceof ArticleVoR) {
            $this->assertCount(1, $changeSet->getDeletes());
            $delete = $changeSet->getDeletes()[0];
            $this->assertStringStartsWith('reviewed-preprint-', $delete, 'The ID of the delete should be assigned an appropriate prefix.');
        } else {
            $this->assertCount(0, $changeSet->getDeletes());
        }
    }

    public static function articleWithElifeAssessmentSignificanceProvider(): Traversable
    {
        foreach (self::modelProvider() as $key => $arguments) {
            /** @var ArticleVersion $articleVersion */
            $articleVersion = $arguments[0];
            if ($articleVersion->getElifeAssessment() && !empty($articleVersion->getElifeAssessment()->getSignificance())) {
                yield $key => [$articleVersion];
            }
        }
    }

    #[DataProvider('articleWithElifeAssessmentSignificanceProvider')]
    #[Test]
    public function testIndexOfArticleWithElifeAssessmentSignificance(ArticleVersion $articleVersion)
    {
        $this->assertNotNull($articleVersion->getElifeAssessment());

        $changeSet = $this->indexer->prepareChangeSet($articleVersion);
        $this->assertCount(1, $changeSet->getInserts());
        $insert = $changeSet->getInserts()[0];

        $articleJson = json_decode($insert['json'], true);
        $this->markTestSkipped('failing test due to missing behaviour');
        /** @phpstan-ignore deadCode.unreachable */
        $this->assertArrayHasKey('elifeAssessment', $articleJson);
        $this->assertArrayHasKey('significance', $articleJson['elifeAssessment']);
    }

    public function testStatusDateIsUsedAsTheSortDateWhenThereIsNoRdsArticle()
    {
        $indexer = new ArticleIndexer(
            $this->getSerializer(),
            ['article-2' => ['date' => '2020-09-08T07:06:05Z']]
        );

        $article = $this->getArticle();
        $changeSet = $indexer->prepareChangeSet($article);

        $return = json_decode($changeSet->getInserts()[0]['json'], true);

        $this->assertSame('2010-02-03T04:05:06Z', $return['sortDate']);
    }

    public function testRdsDateIsUsedAsTheSortDateWhenThereIsAnRdsArticle()
    {
        $indexer = new ArticleIndexer(
            $this->getSerializer(),
            ['article-2' => ['date' => '2020-09-08T07:06:05Z']]
        );

        $article = $this->getArticle(2);
        $changeSet = $indexer->prepareChangeSet($article);

        $return = json_decode($changeSet->getInserts()[0]['json'], true);

        $this->assertSame('2020-09-08T07:06:05Z', $return['sortDate']);
    }

    private function getArticle($id = 1, $status = 'poa')
    {
        $sanitisedStatus = ($status === 'vor') ? 'vor' : 'poa';

        return $this->getSerializer()->denormalize(array_filter([
            'id' => 'article-'.$id,
            'stage' => 'published',
            'version' => 4,
            'type' => 'research-article',
            'doi' => 'DOI',
            'title' => 'title',
            'statusDate' => '2010-02-03T04:05:06Z',
            'reviewedDate' => '2020-09-08T07:06:05Z',
            'curationLabels' => ['foo', 'bar'],
            'volume' => 1,
            'elocationId' => 'elocationId',
            'copyright' => [
                'license' => 'license',
                'statement' => 'statement',
            ],
            'body' => ($sanitisedStatus === 'vor') ? [
                [
                    "type" => "section",
                    "id" => "s-1",
                    "title" => "Introduction",
                    "content" => [
                        [
                            "type" => "paragraph",
                            "text" => "Introduction text."
                        ]
                    ]
                ]
            ] : null,
            'status' => $sanitisedStatus,
        ]), ($sanitisedStatus === 'vor') ? ArticleVoR::class : ArticlePoA::class);
    }
}
