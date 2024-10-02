<?php

namespace tests\eLife\Search\Workflow;

use ComposerLocator;
use eLife\Search\Api\Elasticsearch\MappedElasticsearchClient;
use eLife\Search\Api\HasSearchResultValidator;
use eLife\Search\Workflow\AbstractWorkflow;
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
     * @var AbstractWorkflow
     */
    protected $workflow;
    protected $elastic;
    protected $validator;

    public function setUp()
    {
        $this->elastic = Mockery::mock(MappedElasticsearchClient::class);

        $logger = new ExceptionNullLogger();
        $this->validator = Mockery::mock(HasSearchResultValidator::class);
        $this->workflow = $this->setWorkflow(
            $this->getSerializer(),
            $logger,
            $this->elastic,
            $this->validator
        );
    }

    abstract protected function setWorkflow(
        Serializer $serializer,
        LoggerInterface $logger,
        MappedElasticsearchClient $client,
        HasSearchResultValidator $validator
    ) : AbstractWorkflow;

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
        $paths = [];
        $model = $this->getModel() ?? $model;
        $version = $this->getVersion() ?? $version;
        $modelClass = $this->getModelClass() ?? $modelClass;

        $paths[] = ComposerLocator::getPath('elife/api') . "/dist/samples/{$model}/v{$version}";

        // Collect local fixtures
        if (is_dir($localPath = __DIR__ . "/../../../Fixtures/{$model}/v{$version}")) {
            $paths[] = $localPath;
        }

        $finder = new Finder();

        $finder->files()->in($paths)->name('*.json');

        // Iterate over files found by Finder
        foreach ($finder as $file) {
            $name = "{$model}/v{$version}/{$file->getBasename()}";
            $contents = json_decode($file->getContents(), true);
            $object = $this->getSerializer()->denormalize($contents, $modelClass);
            yield $name => [$object];
        }
    }
}
