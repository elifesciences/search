<?php

namespace tests\eLife\Search\Workflow;

use ComposerLocator;
use Mockery;
use PHPUnit_Framework_TestCase;
use Symfony\Component\Finder\Finder;
use tests\eLife\Search\AsyncAssert;
use tests\eLife\Search\HttpMocks;
use Traversable;
use function GuzzleHttp\json_decode;

abstract class WorkflowTestCase extends PHPUnit_Framework_TestCase
{
    use AsyncAssert;
    use HttpMocks;
    use GetSerializer;
    use GetValidator;

    protected function getModel() : ?string
    {
        return null;
    }

    protected function getModelClass() : ?string
    {
        return null;
    }

    protected function getVersion() : ?int
    {
        return null;
    }

    public function asyncTearDown()
    {
        Mockery::close();
        parent::tearDown();
    }

    public function workflowProvider(string $model = null, string $modelClass = null, int $version = null) : Traversable
    {
        foreach ($this->findSamples($this->getModel() ?? $model, $this->getVersion() ?? $version) as $sample) {
            $object = $this->getSerializer()->denormalize($sample[1], $this->getModelClass() ?? $modelClass);
            yield [$sample[0] => $object];
        }
    }

    final protected function findSamples(string $model, int $version) : Traversable
    {
        $samples = Finder::create()->files()->in(
            ComposerLocator::getPath('elife/api')."/dist/samples/{$model}/v{$version}"
        );
        foreach ($samples as $sample) {
            $name = "{$model}/v{$version}/{$sample->getBasename()}";
            $contents = json_decode($sample->getContents(), true);

            yield [$name, $contents];
        }
    }
}
