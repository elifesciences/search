<?php

namespace tests\eLife\Search\Workflow;

use ComposerLocator;
use eLife\Search\Api\ApiValidator;
use eLife\Search\Api\Elasticsearch\MappedElasticsearchClient;
use eLife\Search\Workflow\Workflow;
use Mockery;
use PHPUnit_Framework_TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Serializer\Serializer;
use tests\eLife\Search\AsyncAssert;
use tests\eLife\Search\ExceptionNullLogger;
use tests\eLife\Search\HttpMocks;
use Traversable;
use function GuzzleHttp\json_decode;

abstract class WorkflowTestCase extends PHPUnit_Framework_TestCase
{
    use AsyncAssert;
    use HttpMocks;
    use GetSerializer;
    use GetValidator;

    /**
     * @var Workflow
     */
    protected $workflow;
    protected $elastic;
    protected $validator;

    public function setUp()
    {
        $this->elastic = Mockery::mock(MappedElasticsearchClient::class);

        $logger = new ExceptionNullLogger();
        $this->validator = $this->getValidator();
        $this->workflow = $this->setWorkflow($this->getSerializer(), $logger, $this->elastic, $this->validator);
    }

    abstract protected function setWorkflow(Serializer $serializer, LoggerInterface $logger, MappedElasticsearchClient $client, ApiValidator $validator) : Workflow;

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
        $model = $this->getModel() ?? $model;
        $version = $this->getVersion() ?? $version;

        $samples = Finder::create()->files()->in(
            ComposerLocator::getPath('elife/api')."/dist/samples/{$model}/v{$version}"
        );

        foreach ($samples as $sample) {
            $name = "{$model}/v{$version}/{$sample->getBasename()}";
            $contents = json_decode($sample->getContents(), true);
            $object = $this->getSerializer()->denormalize($contents, $this->getModelClass() ?? $modelClass);
            yield $name => [$object];
        }
    }
}
