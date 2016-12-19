<?php

namespace tests\eLife\Search\Gearman {

    use Closure;
    use Doctrine\Common\Annotations\AnnotationReader;
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
            if (!class_exists('GearmanWorker')) {
                $this->markTestSkipped('Gearman must be installed to run these tests');
            }

            $this->taskDriver = new GearmanTaskDriver(
                new AnnotationReader(),
                new GearmanWorker(),
                new GearmanClientMock(),
                $this->createMock(LoggerInterface::class),
                false
            );
        }

        /**
         * @test
         */
        public function test_can_instantiate()
        {
            $this->assertInstanceOf(GearmanTaskDriver::class, $this->taskDriver);
        }

        /**
         * @test
         */
        public function test_can_read_annotations()
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
        public function test_can_read_annotations_with_flow()
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
        public function test_can_read_annotations_with_params()
        {
            $this->taskDriver->registerWorkflow(new MockAnnotations\MockClassWithNamedAndParametersAnnotations());
            $this->assertContainsOnlyInstancesOf(GearmanTaskInstance::class, $this->taskDriver->tasks);
            $this->taskDriver->map(Closure::bind(function ($item) {
                $this->assertSame('testing_c', $item->name);
                $this->assertSame('testingCMethod', $item->method);
                $this->assertSame(['testA', 'testB'], $item->parameters);
            }, $this));
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
    }

    class MockClassWithNamedAndFlowAnnotations implements Workflow
    {
        /**
         * @GearmanTask(name="testing_b", next="testing_c");
         */
        public function testingBMethod()
        {
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
    }

}
