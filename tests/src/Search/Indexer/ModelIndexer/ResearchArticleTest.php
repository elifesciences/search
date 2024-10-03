<?php

namespace tests\eLife\Search\Indexer\ModelIndexer;

use PHPUnit_Framework_TestCase;
use Mockery;
use eLife\ApiSdk\Model\ArticleVoR;
use eLife\ApiSdk\Model\ArticlePoA;
use eLife\ApiSdk\Model\ArticleVersion;
use eLife\Search\Api\Elasticsearch\MappedElasticsearchClient;
use eLife\Search\Indexer\ModelIndexer\ResearchArticleIndexer;
use Traversable;

final class ResearchArticleIndexerTest extends PHPUnit_Framework_TestCase
{
    use GetSerializer;
    use ModelProvider;

    /**
     * @var MockInterface
     */
    private $elastic;

    /**
     * @var ResearchArticleIndexer
     */
    private $indexer;

    protected function setUp()
    {
        $this->elastic = Mockery::mock(MappedElasticsearchClient::class);
        $this->indexer = new ResearchArticleIndexer($this->getSerializer(), []);
    }

    protected function getModelDefinitions()
    {
        return [
            ['model' => 'article-vor', 'modelClass' => ArticleVoR::class, 'version' => 8],
            ['model' => 'article-poa', 'modelClass' => ArticlePoA::class, 'version' => 4],
        ];
    }

    /**
     * @dataProvider modelProvider
     * @test
     */
    public function testIndexOfResearchArticle(ArticleVersion $researchArticle)
    {
        $changeSet = $this->indexer->prepareChangeSet($researchArticle);

        $this->assertCount(1, $changeSet->getInserts());

        $insert = $changeSet->getInserts()[0];
        $article = $insert['json'];
        $id = $insert['id'];
        $this->assertJson($article, 'Article is not valid JSON');
        $this->assertNotNull($id, 'An ID is required.');
        $this->assertStringStartsWith('research-article-', $id, 'ID should be assigned an appropriate prefix.');

        if ($researchArticle instanceof ArticleVoR) {
            $this->assertCount(1, $changeSet->getDeletes());
            $delete = $changeSet->getDeletes()[0];
            $this->assertStringStartsWith('reviewed-preprint-', $delete, 'The ID of the delete should be assigned an appropriate prefix.');
        } else {
            $this->assertCount(0, $changeSet->getDeletes());
        }
    }

    public function testStatusDateIsUsedAsTheSortDateWhenThereIsNoRdsArticle()
    {
        $indexer = new ResearchArticleIndexer(
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
        $indexer = new ResearchArticleIndexer(
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
