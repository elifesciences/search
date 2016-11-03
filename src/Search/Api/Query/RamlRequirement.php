<?php

namespace eLife\Search\Api\Query;

trait RamlRequirement
{
    public static $root = __DIR__.'/../../../../vendor/elife/api/dist/samples/';

    public function getFixture(string $name) : string
    {
        $file = self::$root.$name;

        return file_exists($file) ? file_get_contents($file) : null;
    }
}
