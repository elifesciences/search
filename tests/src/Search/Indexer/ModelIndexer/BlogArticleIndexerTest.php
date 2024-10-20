<?php

namespace tests\eLife\Search\Indexer\ModelIndexer;

use PHPUnit\Framework\TestCase;
use eLife\ApiSdk\Model\BlogArticle;
use eLife\Search\Indexer\ModelIndexer\BlogArticleIndexer;
use tests\eLife\Search\HttpMocks;

final class BlogArticleIndexerTest extends TestCase
{
    use GetSerializer;
    use CallSerializer;
    use ModelProvider;
    use HttpMocks;

    /**
     * @var BlogArticleIndexer
     */
    private $indexer;

    protected function setUp(): void
    {
        $this->indexer = new BlogArticleIndexer($this->getSerializer());
    }

    protected function getModelDefinitions(): array
    {
        return [
            ['model' => 'blog-article', 'modelClass' => BlogArticle::class, 'version' => 2]
        ];
    }


    /**
     * @dataProvider modelProvider
     * @test
     */
    public function testSerializationSmokeTest(BlogArticle $blogArticle)
    {
        // Mock the HTTP call that's made for subjects.
        $this->mockSubjects();

        // Check A to B
        $serialized = $this->callSerialize($this->indexer, $blogArticle);
        /** @var BlogArticle $deserialized */
        $deserialized = $this->callDeserialize($this->indexer, $serialized);
        $this->assertInstanceOf(BlogArticle::class, $deserialized);
        // Check B to A
        $final_serialized = $this->callSerialize($this->indexer, $deserialized);
        $this->assertJsonStringEqualsJsonString($serialized, $final_serialized);
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
