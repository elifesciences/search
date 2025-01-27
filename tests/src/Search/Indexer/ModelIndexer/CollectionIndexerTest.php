<?php

namespace tests\eLife\Search\Indexer\ModelIndexer;

use eLife\ApiSdk\Client\Collections;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use eLife\ApiSdk\Model\Collection;
use eLife\Search\Indexer\ModelIndexer\CollectionIndexer;

final class CollectionIndexerTest extends TestCase
{
    use GetSerializer;
    use CallSerializer;
    use ModelProvider;

    /**
     * @var CollectionIndexer
     */
    private $indexer;

    protected function setUp(): void
    {
        $this->indexer = new CollectionIndexer($this->getSerializer());
    }

    protected static function getModelDefinitions(): array
    {
        return [
            ['model' => 'collection', 'modelClass' => Collection::class, 'version' => Collections::VERSION_COLLECTION]
        ];
    }

    #[DataProvider('modelProvider')]
    #[Test]
    public function testSerializationSmokeTest(Collection $collection)
    {
        // Check A to B
        $serialized = $this->callSerialize($this->indexer, $collection);
        /** @var Collection $deserialized */
        $deserialized = $this->callDeserialize($this->indexer, $serialized);
        $this->assertInstanceOf(Collection::class, $deserialized);
        // Check B to A
        $final_serialized = $this->callSerialize($this->indexer, $deserialized);
        $this->assertJsonStringEqualsJsonString($serialized, $final_serialized);
    }

    #[DataProvider('modelProvider')]
    #[Test]
    public function testIndexOfCollection(Collection $collection)
    {
        $changeSet = $this->indexer->prepareChangeSet($collection);

        $this->assertCount(0, $changeSet->getDeletes());
        $this->assertCount(1, $changeSet->getInserts());

        $insert = $changeSet->getInserts()[0];
        $article = $insert['json'];
        $id = $insert['id'];
        $this->assertJson($article, 'Collection is not valid JSON');
        $this->assertNotNull($id, 'An ID is required.');
        $this->assertStringStartsWith('collection-', $id, 'ID should be assigned an appropriate prefix.');
    }
}
