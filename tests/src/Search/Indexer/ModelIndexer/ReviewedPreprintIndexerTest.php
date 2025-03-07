<?php

namespace tests\eLife\Search\Indexer\ModelIndexer;

use eLife\ApiSdk\Client\ReviewedPreprints;
use eLife\ApiSdk\Model\ReviewedPreprint;
use eLife\Search\Indexer\ModelIndexer\ReviewedPreprintLifecycle;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use eLife\Search\Indexer\ModelIndexer\ReviewedPreprintIndexer;
use Traversable;

final class ReviewedPreprintIndexerTest extends TestCase
{
    use GetSerializer;
    use CallSerializer;
    use ModelProvider;

    private ReviewedPreprintIndexer $indexer;

    private Stub&ReviewedPreprintLifecycle $reviewedPreprintLifecycle;

    protected function setUp(): void
    {
        $this->reviewedPreprintLifecycle = $this->createStub(ReviewedPreprintLifecycle::class);
        $this->indexer = new ReviewedPreprintIndexer($this->getSerializer(), $this->reviewedPreprintLifecycle);
    }

    protected static function getModelDefinitions(): array
    {
        return [
            ['model' => 'reviewed-preprint', 'modelClass' => ReviewedPreprint::class, 'version' => ReviewedPreprints::VERSION_REVIEWED_PREPRINT]
        ];
    }

    #[DataProvider('modelProvider')]
    #[Test]
    public function testSerializationSmokeTest(ReviewedPreprint $reviewedPreprint)
    {
        // Check A to B
        $serialized = $this->callSerialize($this->indexer, $reviewedPreprint);
        /** @var ReviewedPreprint $deserialized */
        $deserialized = $this->callDeserialize($this->indexer, $serialized);
        $this->assertInstanceOf(ReviewedPreprint::class, $deserialized);
        // Check B to A
        $final_serialized = $this->callSerialize($this->indexer, $deserialized);
        $this->assertJsonStringEqualsJsonString($serialized, $final_serialized);
    }

    #[DataProvider('modelProvider')]
    #[Test]
    public function testGivenAReviewedPreprintThatHasNotBeenSupersededItCreatesAnInsertion(ReviewedPreprint $reviewedPreprint)
    {
        $this->reviewedPreprintLifecycle->method('isSuperseded')->willReturn(false);
        $changeSet = $this->indexer->prepareChangeSet($reviewedPreprint);

        $this->assertCount(0, $changeSet->getDeletes());
        $this->assertCount(1, $changeSet->getInserts());

        $insert = $changeSet->getInserts()[0];
        $article = $insert['json'];
        $id = $insert['id'];
        $this->assertJson($article, 'Article is not valid JSON');
        $this->assertNotNull($id, 'An ID is required.');
        $this->assertStringStartsWith('reviewed-preprint-', $id, 'ID should be assigned an appropriate prefix.');
    }

    public static function reviewedPreprintWithElifeAssessmentSignificanceProvider(): Traversable
    {
        foreach (self::modelProvider() as $key => $arguments) {
            /** @var ReviewedPreprint $reviewedPreprint */
            $reviewedPreprint = $arguments[0];
            if ($reviewedPreprint->getElifeAssessment() && !empty($reviewedPreprint->getElifeAssessment()->getSignificance())) {
                yield $key => [$reviewedPreprint];
            }
        }
    }

    #[DataProvider('reviewedPreprintWithElifeAssessmentSignificanceProvider')]
    #[Test]
    public function testGivenAReviewedPreprintWithElifeAssessmentSignificanceThatHasNotBeenSupersededItCreatesAnInsertion(ReviewedPreprint $reviewedPreprint)
    {
        $this->assertNotNull($reviewedPreprint->getElifeAssessment());

        $this->reviewedPreprintLifecycle->method('isSuperseded')->willReturn(false);
        $changeSet = $this->indexer->prepareChangeSet($reviewedPreprint);

        $this->assertCount(1, $changeSet->getInserts());
        $insert = $changeSet->getInserts()[0];

        $reviewedPreprintJson = json_decode($insert['json'], true);
        $this->assertArrayHasKey('elifeAssessment', $reviewedPreprintJson);
        $this->assertArrayHasKey('significance', $reviewedPreprintJson['elifeAssessment']);
        $this->assertNotEmpty($reviewedPreprintJson['elifeAssessment']['significance']);
    }

    public static function reviewedPreprintWithElifeAssessmentStrengthProvider(): Traversable
    {
        foreach (self::modelProvider() as $key => $arguments) {
            /** @var ReviewedPreprint $reviewedPreprint */
            $reviewedPreprint = $arguments[0];
            if ($reviewedPreprint->getElifeAssessment() && !empty($reviewedPreprint->getElifeAssessment()->getStrength())) {
                yield $key => [$reviewedPreprint];
            }
        }
    }

    #[DataProvider('reviewedPreprintWithElifeAssessmentStrengthProvider')]
    #[Test]
    public function testGivenAReviewedPreprintWithElifeAssessmentStrengthThatHasNotBeenSupersededItCreatesAnInsertion(ReviewedPreprint $reviewedPreprint)
    {
        $this->assertNotNull($reviewedPreprint->getElifeAssessment());

        $this->reviewedPreprintLifecycle->method('isSuperseded')->willReturn(false);
        $changeSet = $this->indexer->prepareChangeSet($reviewedPreprint);

        $this->assertCount(1, $changeSet->getInserts());
        $insert = $changeSet->getInserts()[0];

        $reviewedPreprintJson = json_decode($insert['json'], true);
        $this->assertArrayHasKey('elifeAssessment', $reviewedPreprintJson);
        $this->assertArrayHasKey('strength', $reviewedPreprintJson['elifeAssessment']);
        $this->assertNotEmpty($reviewedPreprintJson['elifeAssessment']['strength']);
    }

    #[DataProvider('modelProvider')]
    #[Test]
    public function testGivenAReviewedPreprintThatHasBeenSupersededItDoesNotCreateAnInsertion(ReviewedPreprint $reviewedPreprint)
    {
        $this->reviewedPreprintLifecycle->method('isSuperseded')->willReturn(true);
        $changeSet = $this->indexer->prepareChangeSet($reviewedPreprint);

        $this->assertCount(0, $changeSet->getDeletes());
        $this->assertCount(0, $changeSet->getInserts());
    }
}
