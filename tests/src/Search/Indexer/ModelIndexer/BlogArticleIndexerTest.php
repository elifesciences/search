<?php

namespace tests\eLife\Search\Indexer\ModelIndexer;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use eLife\ApiSdk\Model\BlogArticle;
use eLife\Search\Indexer\ModelIndexer\BlogArticleIndexer;

final class BlogArticleIndexerTest extends TestCase
{
    use GetSerializer;
    use CallSerializer;
    use ModelProvider;

    /**
     * @var BlogArticleIndexer
     */
    private $indexer;

    protected function setUp(): void
    {
        $this->indexer = new BlogArticleIndexer($this->getSerializer());
    }

    protected static function getModelDefinitions(): array
    {
        return [
            ['model' => 'blog-article', 'modelClass' => BlogArticle::class, 'version' => 2]
        ];
    }


    #[DataProvider('modelProvider')]
    #[Test]
    public function testSerializationSmokeTest(BlogArticle $blogArticle)
    {
        // Check A to B
        $serialized = $this->callSerialize($this->indexer, $blogArticle);
        /** @var BlogArticle $deserialized */
        $deserialized = $this->callDeserialize($this->indexer, $serialized);
        $this->assertInstanceOf(BlogArticle::class, $deserialized);
        // Check B to A
        $final_serialized = $this->callSerialize($this->indexer, $deserialized);
        $this->assertJsonStringEqualsJsonString($serialized, $final_serialized);
    }

    #[DataProvider('modelProvider')]
    #[Test]
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
