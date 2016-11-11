<?php

namespace tests\eLife\Search\Workflow;

use eLife\ApiSdk\Model\LabsExperiment;
use eLife\Search\Api\Elasticsearch\ElasticsearchClient;
use eLife\Search\Workflow\LabsExperimentWorkflow;
use Mockery;
use PHPUnit_Framework_TestCase;
use test\eLife\ApiSdk\Serializer\LabsExperimentNormalizerTest;
use tests\eLife\Search\AsyncAssert;
use tests\eLife\Search\ExceptionNullLogger;
use tests\eLife\Search\HttpMocks;

class LabsExperimentWorkflowTest extends PHPUnit_Framework_TestCase
{
    use AsyncAssert;
    use HttpMocks;
    use GetSerializer;
    use GetValidator;

    /**
     * @var LabsExperimentWorkflow
     */
    private $workflow;
    private $elastic;
    private $validator;

    public function setUp()
    {
        $this->elastic = Mockery::mock(ElasticsearchClient::class);
        $logger = new ExceptionNullLogger();
        $this->validator = $this->getValidator();
        $this->workflow = new LabsExperimentWorkflow($this->getSerializer(), $logger, $this->elastic, $this->validator);
    }

    public function asyncTearDown()
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * @dataProvider labsExperimentProvider
     * @test
     */
    public function testSerializationSmokeTest(LabsExperiment $labsExperiment, array $context = [], array $expected = [])
    {
        // Mock the HTTP call that's made for subjects.
        $this->mockSubjects();
        // Check A to B
        $serialized = $this->workflow->serialize($labsExperiment);
        /** @var LabsExperiment $deserialized */
        $deserialized = $this->workflow->deserialize($serialized);
        $this->assertInstanceOf(LabsExperiment::class, $deserialized);
        // Check B to A
        $final_serialized = $this->workflow->serialize($deserialized);
        $this->assertJsonStringEqualsJsonString($serialized, $final_serialized);
    }

    /**
     * @dataProvider labsExperimentProvider
     * @test
     */
    public function testValidationOfLabsExperiment(LabsExperiment $labsExperiment)
    {
        $return = $this->workflow->validate($labsExperiment);
        $this->assertInstanceOf(LabsExperiment::class, $return);
    }

    /**
     * @dataProvider labsExperimentProvider
     * @test
     */
    public function testIndexOfLabsExperiment(LabsExperiment $labsExperiment)
    {
        $return = $this->workflow->index($labsExperiment);
        $article = $return['json'];
        $type = $return['type'];
        $id = $return['id'];
        $this->assertJson($article, 'LabsExperiment is not valid JSON');
        $this->assertEquals('labs-experiment', $type, 'A type is required.');
        $this->assertNotNull($id, 'An ID is required.');
    }

    /**
     * @dataProvider labsExperimentProvider
     * @test
     */
    public function testInsertOfLabsExperiment(LabsExperiment $labsExperiment)
    {
        $this->elastic->shouldReceive('indexJsonDocument');
        $ret = $this->workflow->insert($this->workflow->serialize($labsExperiment), 'labs-experiment', $labsExperiment->getNumber());
        $this->assertArrayHasKey('type', $ret);
        $this->assertArrayHasKey('id', $ret);
        $id = $ret['id'];
        $type = $ret['type'];
        $this->assertEquals('labs-experiment', $type);
        $this->assertEquals($labsExperiment->getNumber(), $id);
    }

    public function labsExperimentProvider() : array
    {
        return (new LabsExperimentNormalizerTest())->normalizeProvider();
    }
}
