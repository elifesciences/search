<?php

namespace tests\eLife\Search\Indexer\ModelIndexer;

use ComposerLocator;
use Exception;
use Symfony\Component\Finder\Finder;
use Traversable;
use function GuzzleHttp\json_decode;

trait ModelProvider
{
    use GetSerializer;

    /**
     * @return array{'model': string, 'version': int, 'modelClass': string}[]
     */
    abstract protected static function getModelDefinitions(): array;

    public static function modelProvider() : Traversable
    {
        foreach (static::getModelDefinitions() as $modelDefinition) {
            $model = $modelDefinition['model'];
            $version = $modelDefinition['version'];
            $modelClass = $modelDefinition['modelClass'];

            $paths[] = ComposerLocator::getPath('elife/api') . "/dist/samples/{$model}/v{$version}";

            // Collect local fixtures
            $localFixturesPath = __DIR__ . "/../../../../Fixtures/{$model}";
            $localPath = "{$localFixturesPath}/v{$version}";
            if (is_dir($localPath)) {
                $paths[] = $localPath;
            } elseif (is_dir($localFixturesPath)) {
                throw new Exception("Expected local fixtures directory: {$localPath}");
            }

            $finder = new Finder();
            $finder->files()->in($paths)->name('*.json');

            // Iterate over files found by Finder
            foreach ($finder as $file) {
                $name = "{$model}/v{$version}/{$file->getBasename()}";
                $contents = json_decode($file->getContents(), true);
                $object = self::getSerializer()->denormalize($contents, $modelClass);
                // sha needed because name could be duplicated from api samples and local fixtures.
                $sha = substr(sha1($file->getRealPath()), 0, 8);
                yield "{$sha}-{$name}" => [$object];
            }
        }
    }
}
