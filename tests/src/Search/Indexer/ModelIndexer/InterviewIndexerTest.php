<?php

namespace tests\eLife\Search\Indexer\ModelIndexer;

use eLife\ApiSdk\Client\Interviews;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use eLife\ApiSdk\Model\Interview;
use eLife\Search\Indexer\ModelIndexer\InterviewIndexer;

final class InterviewIndexerTest extends TestCase
{
    use GetSerializer;
    use CallSerializer;
    use ModelProvider;

    /**
     * @var InterviewIndexer
     */
    private $indexer;

    protected function setUp(): void
    {
        $this->indexer = new InterviewIndexer($this->getSerializer());
    }

    protected static function getModelDefinitions(): array
    {
        return [
            ['model' => 'interview', 'modelClass' => Interview::class, 'version' => Interviews::VERSION_INTERVIEW]
        ];
    }

    #[DataProvider('modelProvider')]
    #[Test]
    public function testSerializationSmokeTest(Interview $interview)
    {
        // Check A to B
        $serialized = $this->callSerialize($this->indexer, $interview);
        /** @var Interview $deserialized */
        $deserialized = $this->callDeserialize($this->indexer, $serialized);
        $this->assertInstanceOf(Interview::class, $deserialized);
        // Check B to A
        $final_serialized = $this->callSerialize($this->indexer, $deserialized);
        $this->assertJsonStringEqualsJsonString($serialized, $final_serialized);
    }

    #[DataProvider('modelProvider')]
    #[Test]
    public function testIndexOfInterview(Interview $interview)
    {
        $changeSet = $this->indexer->prepareChangeSet($interview);

        $this->assertCount(0, $changeSet->getDeletes());
        $this->assertCount(1, $changeSet->getInserts());

        $insert = $changeSet->getInserts()[0];
        $article = $insert['json'];
        $id = $insert['id'];
        $this->assertJson($article, 'Interview is not valid JSON');
        $this->assertNotNull($id, 'An ID is required.');
        $this->assertStringStartsWith('interview-', $id, 'ID should be assigned an appropriate prefix.');
    }
}
