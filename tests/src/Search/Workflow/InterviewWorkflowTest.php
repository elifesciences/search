<?php

namespace tests\eLife\Search\Workflow;

use eLife\ApiSdk\Model\Interview;
use eLife\Search\Api\Elasticsearch\MappedElasticsearchClient;
use eLife\Search\Workflow\InterviewWorkflow;
use Mockery;
use tests\eLife\Search\ExceptionNullLogger;

class InterviewWorkflowTest extends WorkflowTestCase
{
    /**
     * @var InterviewWorkflow
     */
    private $workflow;
    private $elastic;
    private $validator;

    public function setUp()
    {
        $this->elastic = Mockery::mock(MappedElasticsearchClient::class);
        $logger = new ExceptionNullLogger();
        $this->validator = $this->getValidator();
        $this->workflow = new InterviewWorkflow($this->getSerializer(), $logger, $this->elastic, $this->validator);
    }

    protected function getModel() : string
    {
        return 'interview';
    }

    protected function getModelClass() : string
    {
        return Interview::class;
    }

    protected function getVersion() : int
    {
        return 1;
    }

    /**
     * @dataProvider workflowProvider
     * @test
     */
    public function testSerializationSmokeTest(Interview $interview, array $context = [], array $expected = [])
    {
        // Mock the HTTP call that's made for subjects.
        $this->mockSubjects();
        // Check A to B
        $serialized = $this->workflow->serialize($interview);
        /** @var Interview $deserialized */
        $deserialized = $this->workflow->deserialize($serialized);
        $this->assertInstanceOf(Interview::class, $deserialized);
        // Check B to A
        $final_serialized = $this->workflow->serialize($deserialized);
        $this->assertJsonStringEqualsJsonString($serialized, $final_serialized);
    }

    /**
     * @dataProvider workflowProvider
     * @test
     */
    public function testIndexOfInterview(Interview $interview)
    {
        $return = $this->workflow->index($interview);
        $article = $return['json'];
        $id = $return['id'];
        $this->assertJson($article, 'Interview is not valid JSON');
        $this->assertNotNull($id, 'An ID is required.');
        $this->assertStringStartsWith('interview-', $id, 'ID should be assigned an appropriate prefix.');
    }

    /**
     * @dataProvider workflowProvider
     * @test
     */
    public function testInsertOfInterview(Interview $interview)
    {
        $this->elastic->shouldReceive('indexJsonDocument');
        $ret = $this->workflow->insert($this->workflow->serialize($interview), $interview->getId());
        $this->assertArrayHasKey('id', $ret);
        $id = $ret['id'];
        $this->assertEquals($interview->getId(), $id);
    }
}
