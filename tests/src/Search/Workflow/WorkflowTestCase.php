<?php

namespace tests\eLife\Search\Workflow;

use ComposerLocator;
use Mockery;
use PHPUnit_Framework_TestCase;
use Symfony\Component\Finder\Finder;
use tests\eLife\Search\AsyncAssert;
use tests\eLife\Search\HttpMocks;
use function GuzzleHttp\json_decode;

abstract class WorkflowTestCase extends PHPUnit_Framework_TestCase
{
    use AsyncAssert;
    use HttpMocks;
    use GetSerializer;
    use GetValidator;

    protected $apiSdk;
    protected $denormalizer;
    protected $model;

    abstract protected function setUpSerializer();

    abstract protected function getModel();

    abstract protected function getModelClass();

    abstract protected function getVersion();

    public function asyncTearDown()
    {
        Mockery::close();
        parent::tearDown();
    }

    final public function workflowProvider() : \Traversable
    {
        $this->setUpSerializer();
        foreach ($this->findSamples() as $sample) {
            $object = $this->denormalizer->denormalize($sample[1], $this->getModelClass());
            yield [$sample[0] => $object];
        }
    }

    public function findSamples()
    {
        $samples = Finder::create()->files()->in(
            ComposerLocator::getPath('elife/api')."/dist/samples/{$this->getModel()}/v{$this->getVersion()}"
        );
        foreach ($samples as $sample) {
            $name = "{$this->getModel()}/v{$this->getVersion()}/{$sample->getBasename()}";
            $contents = json_decode($sample->getContents(), true);

            yield [$name, $contents];
        }
    }
}
