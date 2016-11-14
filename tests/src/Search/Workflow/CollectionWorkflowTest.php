<?php

namespace tests\eLife\Search\Workflow;

use eLife\ApiSdk\Model\Collection;
use eLife\Search\Api\Elasticsearch\ElasticsearchClient;
use eLife\Search\Workflow\CollectionWorkflow;
use Mockery;
use PHPUnit_Framework_TestCase;
use test\eLife\ApiSdk\Serializer\CollectionNormalizerTest;
use tests\eLife\Search\AsyncAssert;
use tests\eLife\Search\ExceptionNullLogger;
use tests\eLife\Search\HttpMocks;

class CollectionWorkflowTest extends PHPUnit_Framework_TestCase
{
    use AsyncAssert;
    use HttpMocks;
    use GetSerializer;
    use GetValidator;

    /**
     * @var CollectionWorkflow
     */
    private $workflow;
    private $elastic;
    private $validator;

    public function setUp()
    {
        $this->elastic = Mockery::mock(ElasticsearchClient::class);
        $logger = new ExceptionNullLogger();
        $this->validator = $this->getValidator();
        $this->workflow = new CollectionWorkflow($this->getSerializer(), $logger, $this->elastic, $this->validator);
    }

    public function asyncTearDown()
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * @dataProvider collectionProvider
     * @test
     */
    public function testSerializationSmokeTest(Collection $collection, array $context = [], array $expected = [])
    {
        // Mock the HTTP call that's made for subjects.
        $this->mockSubjects();
        // Check A to B
        $serialized = $this->workflow->serialize($collection);
        /** @var Collection $deserialized */
        $deserialized = $this->workflow->deserialize($serialized);
        $this->assertInstanceOf(Collection::class, $deserialized);
        // Check B to A
        $final_serialized = $this->workflow->serialize($deserialized);
        $this->assertJsonStringEqualsJsonString($serialized, $final_serialized);
    }

    /**
     * @dataProvider collectionProvider
     * @test
     */
    public function testValidationOfCollection(Collection $collection)
    {
        $return = $this->workflow->validate($collection);
        $this->assertInstanceOf(Collection::class, $return);
    }

    /**
     * @dataProvider collectionProvider
     * @test
     */
    public function testIndexOfCollection(Collection $collection)
    {
        $return = $this->workflow->index($collection);
        $article = $return['json'];
        $type = $return['type'];
        $id = $return['id'];
        $this->assertJson($article, 'Collection is not valid JSON');
        $this->assertEquals('collection', $type, 'A type is required.');
        $this->assertNotNull($id, 'An ID is required.');
    }

    /**
     * @dataProvider collectionProvider
     * @test
     */
    public function testInsertOfCollection(Collection $collection)
    {
        $this->elastic->shouldReceive('indexJsonDocument');
        $ret = $this->workflow->insert($this->workflow->serialize($collection), 'collection', $collection->getId());
        $this->assertArrayHasKey('type', $ret);
        $this->assertArrayHasKey('id', $ret);
        $id = $ret['id'];
        $type = $ret['type'];
        $this->assertEquals('collection', $type);
        $this->assertEquals($collection->getId(), $id);
    }

    public function collectionProvider() : array
    {
        return (new CollectionNormalizerTest())->normalizeProvider();
    }
}
