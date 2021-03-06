<?php

namespace tests\eLife\Search;

use ComposerLocator;
use LogicException;

trait RamlRequirement
{
    public function getFixtureWithType(string $name, string $type) : string
    {
        $fixture = $this->getFixture($name);
        $fixture = json_decode($fixture);
        $fixture->type = $type;

        return json_encode($fixture, JSON_PRETTY_PRINT);
    }

    public function getFixture(string $name) : string
    {
        $file = ComposerLocator::getPath('elife/api').'/dist/samples/'.$name;
        if (file_exists($file)) {
            return file_get_contents($file);
        } else {
            if (!method_exists($this, 'markTestSkipped')) {
                throw new LogicException('This trait should only be used in test cases.');
            }
            $this->markTestSkipped('RAML json not installed, skipping test.');
        }

        return null;
    }
}
