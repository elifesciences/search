<?php

namespace tests\eLife\Search\Indexer\ModelIndexer;

use PHPUnit_Framework_TestCase;
use eLife\ApiSdk\Model\LabsPost;
use eLife\Search\Indexer\ModelIndexer\LabsPostIndexer;


final class LabsPostIndexerTest extends PHPUnit_Framework_TestCase
{
    use GetSerializer;
    use ModelProvider;

    /**
     * @var LabsPostIndexer
     */
    private $indexer;

    protected function setUp()
    {
        $this->indexer = new LabsPostIndexer($this->getSerializer());
    }

    protected function getModelDefinitions()
    {
        return [
            ['model' => 'labs-post', 'modelClass' => LabsPost::class, 'version' => 1]
        ];
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
