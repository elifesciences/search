<?php

namespace tests\eLife\Search;

use ComposerLocator;
use LogicException;

trait RamlRequirement
{
    abstract public static function markTestSkipped(string $message = '');

    public static function getFixtureWithType(string $name, string $type) : string
    {
        $fixture = self::getFixture($name);
        $fixture = json_decode($fixture);
        $fixture->type = $type;

        return json_encode($fixture, JSON_PRETTY_PRINT);
    }

    public static function getFixture(string $name) : string
    {
        $file = ComposerLocator::getPath('elife/api').'/dist/samples/'.$name;
        if (file_exists($file)) {
            return file_get_contents($file);
        }
        throw new LogicException("Fixture {$name} does not exist");
    }
}
