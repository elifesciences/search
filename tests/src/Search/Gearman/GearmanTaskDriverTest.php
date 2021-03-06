<?php

namespace tests\eLife\Search\Gearman {
    use Closure;
    use Doctrine\Common\Annotations\AnnotationReader;
    use eLife\Bus\Limit\CallbackLimit;
    use eLife\Logging\Monitoring;
    use eLife\Search\Annotation\GearmanTaskDriver;
    use eLife\Search\Annotation\GearmanTaskInstance;
    use eLife\Search\Annotation\Register;
    use GearmanWorker;
    use MockAnnotations;
    use PHPUnit_Framework_TestCase;
    use Psr\Log\LoggerInterface;

    class GearmanTaskDriverTest extends PHPUnit_Framework_TestCase
    {
        /**
         * @var GearmanTaskDriver
         */
        private $taskDriver;

        public function setUp()
        {
            Register::registerLoader();

            $this->limitReached = false;
            $this->logger = $this->createMock(LoggerInterface::class);
            $this->monitoring = new Monitoring();
            $this->taskDriver = new GearmanTaskDriver(
                new AnnotationReader(),
                new GearmanWorker(),
                new GearmanClientMock(),
                $this->logger,
                $this->monitoring,
                new CallbackLimit(function () {
                    return $this->limitReached;
                })
            );
        }

        /**
         * @test
         */
        public function testCanInstantiate()
        {
            $this->assertInstanceOf(GearmanTaskDriver::class, $this->taskDriver);
        }

        /**
         * @test
         */
        public function testCanReadAnnotations()
        {
            $this->taskDriver->registerWorkflow(new MockAnnotations\MockClassWithNamedAnnotations());
            $this->assertContainsOnlyInstancesOf(GearmanTaskInstance::class, $this->taskDriver->tasks);
            $this->taskDriver->map(Closure::bind(function ($item) {
                $this->assertSame('testing_a', $item->name);
                $this->assertSame('testingAMethod', $item->method);
            }, $this));
        }

        /**
         * @test
         */
        public function testCanReadAnnotationsWithFlow()
        {
            $this->taskDriver->registerWorkflow(new MockAnnotations\MockClassWithNamedAndFlowAnnotations());
            $this->assertContainsOnlyInstancesOf(GearmanTaskInstance::class, $this->taskDriver->tasks);
            $this->taskDriver->map(Closure::bind(function ($item) {
                $this->assertSame('testing_b', $item->name);
                $this->assertSame('testingBMethod', $item->method);
                $this->assertSame('testing_c', $item->next);
            }, $this));
        }

        /**
         * @test
         */
        public function testCanReadAnnotationsWithParams()
        {
            $this->taskDriver->registerWorkflow(new MockAnnotations\MockClassWithNamedAndParametersAnnotations());
            $this->assertContainsOnlyInstancesOf(GearmanTaskInstance::class, $this->taskDriver->tasks);
            $this->taskDriver->map(Closure::bind(function ($item) {
                $this->assertSame('testing_c', $item->name);
                $this->assertSame('testingCMethod', $item->method);
                $this->assertSame(['testA', 'testB'], $item->parameters);
            }, $this));
        }

        /**
         * @test
         */
        public function exitsOnLimitBeingReached()
        {
            $this->logLines = [];
            $this->logger->expects($this->any())
                ->method('info')
                ->will($this->returnCallback(function ($logLine) {
                    $this->logLines[] = $logLine;
                })); // start and stop

            $this->limitReached = true;
            $this->taskDriver->work();

            $this->assertEquals(
                [
                    'gearman:worker: Started listening.',
                    'gearman:worker: Stopped because of limits reached.',
                ],
                $this->logLines
            );
        }
    }
}

namespace MockAnnotations {
    use eLife\Search\Annotation\GearmanTask;
    use eLife\Search\Workflow\Workflow;

    class MockClassWithNamedAnnotations implements Workflow
    {
        /**
         * @GearmanTask(name="testing_a");
         */
        public function testingAMethod()
        {
        }

        public function getSdkClass() : string
        {
            return 'stdClass';
        }
    }

    class MockClassWithNamedAndFlowAnnotations implements Workflow
    {
        /**
         * @GearmanTask(name="testing_b", next="testing_c");
         */
        public function testingBMethod()
        {
        }

        public function getSdkClass() : string
        {
            return 'stdClass';
        }
    }

    class MockClassWithNamedAndParametersAnnotations implements Workflow
    {
        /**
         * @GearmanTask(name="testing_c", parameters={"testA", "testB"});
         */
        public function testingCMethod()
        {
        }

        public function getSdkClass() : string
        {
            return 'stdClass';
        }
    }
}
