<?php

namespace tests\eLife\Search\Indexer\ModelIndexer;

use PHPUnit\Framework\TestCase;
use eLife\ApiSdk\Model\LabsPost;
use eLife\Search\Indexer\ModelIndexer\LabsPostIndexer;

final class LabsPostIndexerTest extends TestCase
{
    use GetSerializer;
    use CallSerializer;
    use ModelProvider;

    /**
     * @var LabsPostIndexer
     */
    private $indexer;

    protected function setUp(): void
    {
        $this->indexer = new LabsPostIndexer($this->getSerializer());
    }

    protected function getModelDefinitions(): array
    {
        return [
            ['model' => 'labs-post', 'modelClass' => LabsPost::class, 'version' => 1]
        ];
    }

    /**
     * @dataProvider modelProvider
     * @test
     */
    public function testSerializationSmokeTest(LabsPost $labsPost)
    {
        // Check A to B
        $serialized = $this->callSerialize($this->indexer, $labsPost);
        /** @var LabsPost $deserialized */
        $deserialized = $this->callDeserialize($this->indexer, $serialized);
        $this->assertInstanceOf(LabsPost::class, $deserialized);
        // Check B to A
        $final_serialized = $this->callSerialize($this->indexer, $deserialized);
        $this->assertJsonStringEqualsJsonString($serialized, $final_serialized);
    }

    /**
     * @dataProvider modelProvider
     * @test
     */
    public function testIndexOfLabsPost(LabsPost $labsPost)
    {
        $changeSet = $this->indexer->prepareChangeSet($labsPost);

        $this->assertCount(0, $changeSet->getDeletes());
        $this->assertCount(1, $changeSet->getInserts());

        $insert = $changeSet->getInserts()[0];
        $article = $insert['json'];
        $id = $insert['id'];
        $this->assertJson($article, 'LabsPost is not valid JSON');
        $this->assertNotNull($id, 'An ID is required.');
        $this->assertStringStartsWith('labs-post-', $id, 'ID should be assigned an appropriate prefix.');
    }
}
