<?php

namespace tests\eLife\Search\Workflow;

use eLife\ApiSdk\Model\LabsPost;
use eLife\Search\Api\Elasticsearch\MappedElasticsearchClient;
use eLife\Search\Workflow\LabsPostWorkflow;
use Mockery;
use PHPUnit_Framework_TestCase;
use test\eLife\ApiSdk\Serializer\LabsPostNormalizerTest;
use tests\eLife\Search\AsyncAssert;
use tests\eLife\Search\ExceptionNullLogger;
use tests\eLife\Search\HttpMocks;

class LabsPostWorkflowTest extends PHPUnit_Framework_TestCase
{
    use AsyncAssert;
    use HttpMocks;
    use GetSerializer;
    use GetValidator;

    /**
     * @var LabsPostWorkflow
     */
    private $workflow;
    private $elastic;
    private $validator;

    public function setUp()
    {
        $this->elastic = Mockery::mock(MappedElasticsearchClient::class);
        $logger = new ExceptionNullLogger();
        $this->validator = $this->getValidator();
        $this->workflow = new LabsPostWorkflow($this->getSerializer(), $logger, $this->elastic, $this->validator);
    }

    public function asyncTearDown()
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * @dataProvider labsPostProvider
     * @test
     */
    public function testSerializationSmokeTest(LabsPost $labsPost, array $context = [], array $expected = [])
    {
        // Mock the HTTP call that's made for subjects.
        $this->mockSubjects();
        // Check A to B
        $serialized = $this->workflow->serialize($labsPost);
        /** @var LabsPost $deserialized */
        $deserialized = $this->workflow->deserialize($serialized);
        $this->assertInstanceOf(LabsPost::class, $deserialized);
        // Check B to A
        $final_serialized = $this->workflow->serialize($deserialized);
        $this->assertJsonStringEqualsJsonString($serialized, $final_serialized);
    }

    /**
     * @dataProvider labsPostProvider
     * @test
     */
    public function testIndexOfLabsPost(LabsPost $labsPost)
    {
        $return = $this->workflow->index($labsPost);
        $article = $return['json'];
        $id = $return['id'];
        $this->assertJson($article, 'LabsPost is not valid JSON');
        $this->assertNotNull($id, 'An ID is required.');
        $this->assertStringStartsWith('labs-post-', $id, 'ID should be assigned an appropriate prefix.');
    }

    /**
     * @dataProvider labsPostProvider
     * @test
     */
    public function testInsertOfLabsPost(LabsPost $labsPost)
    {
        $this->elastic->shouldReceive('indexJsonDocument');
        $ret = $this->workflow->insert($this->workflow->serialize($labsPost), $labsPost->getId());
        $this->assertArrayHasKey('id', $ret);
        $id = $ret['id'];
        $this->assertEquals($labsPost->getId(), $id);
    }

    public function labsPostProvider() : array
    {
        return (new LabsPostNormalizerTest())->normalizeProvider();
    }
}
