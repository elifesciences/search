<?php

namespace tests\eLife\Search\Workflow;

use eLife\ApiSdk\Model\Interview;
use eLife\Search\Api\Elasticsearch\MappedElasticsearchClient;
use eLife\Search\Workflow\InterviewWorkflow;
use Mockery;
use PHPUnit_Framework_TestCase;
use test\eLife\ApiSdk\Serializer\InterviewNormalizerTest;
use tests\eLife\Search\AsyncAssert;
use tests\eLife\Search\ExceptionNullLogger;
use tests\eLife\Search\HttpMocks;

class InterviewWorkflowTest extends PHPUnit_Framework_TestCase
{
    use AsyncAssert;
    use HttpMocks;
    use GetSerializer;
    use GetValidator;

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

    public function asyncTearDown()
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * @dataProvider interviewProvider
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
     * @dataProvider interviewProvider
     * @test
     */
    public function testIndexOfInterview(Interview $interview)
    {
        $return = $this->workflow->index($interview);
        $article = $return['json'];
        $type = $return['type'];
        $id = $return['id'];
        $this->assertJson($article, 'Interview is not valid JSON');
        $this->assertEquals('interview', $type, 'A type is required.');
        $this->assertNotNull($id, 'An ID is required.');
    }

    /**
     * @dataProvider interviewProvider
     * @test
     */
    public function testInsertOfInterview(Interview $interview)
    {
        $this->elastic->shouldReceive('indexJsonDocument');
        $ret = $this->workflow->insert($this->workflow->serialize($interview), 'interview', $interview->getId());
        $this->assertArrayHasKey('type', $ret);
        $this->assertArrayHasKey('id', $ret);
        $id = $ret['id'];
        $type = $ret['type'];
        $this->assertEquals('interview', $type);
        $this->assertEquals($interview->getId(), $id);
    }

    public function interviewProvider() : array
    {
        return (new InterviewNormalizerTest())->normalizeProvider();
    }
}
