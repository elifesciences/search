<?php

namespace tests\eLife\Search\Indexer\ModelIndexer;

use ComposerLocator;
use Symfony\Component\Finder\Finder;
use Traversable;
use function GuzzleHttp\json_decode;

trait ModelProvider
{
    /**
     * @return array{'model': string, 'version': int, 'modelClass': string}[]
     */
    abstract protected function getModelDefinitions(): array;

    public function modelProvider() : Traversable
    {
        $paths = [];

        foreach ($this->getModelDefinitions() as $modelDefinition) {
            $model = $modelDefinition['model'];
            $version = $modelDefinition['version'];
            $modelClass = $modelDefinition['modelClass'];

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
}
