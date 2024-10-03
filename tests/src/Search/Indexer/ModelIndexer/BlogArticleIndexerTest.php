<?php

namespace tests\eLife\Search\Indexer\ModelIndexer;

use PHPUnit_Framework_TestCase;
use eLife\ApiSdk\Model\BlogArticle;
use eLife\Search\Indexer\ModelIndexer\BlogArticleIndexer;

final class BlogArticleIndexerTest extends PHPUnit_Framework_TestCase
{
    use GetSerializer;
    use ModelProvider;

    /**
     * @var BlogArticleIndexer
     */
    private $indexer;

    protected function setUp()
    {
        $this->indexer = new BlogArticleIndexer($this->getSerializer());
    }

    protected function getModel() : string
    {
        return 'blog-article';
    }

    protected function getModelClass() : string
    {
        return BlogArticle::class;
    }

    protected function getVersion() : int
    {
        return 2;
    }

    /**
     * @dataProvider modelProvider
     * @test
     */
    public function testIndexOfBlogArticle(BlogArticle $blogArticle)
    {
        $changeSet = $this->indexer->prepareChangeSet($blogArticle);

        $this->assertCount(0, $changeSet->getDeletes());
        $this->assertCount(1, $changeSet->getInserts());
        $insert = $changeSet->getInserts()[0];
        $article = $insert['json'];
        $id = $insert['id'];

        $this->assertJson($article, 'Article is not valid JSON');
        $this->assertNotNull($id, 'An ID is required.');
        $this->assertStringStartsWith('blog-article-', $id, 'ID should be assigned an appropriate prefix.');
    }
}
