<?php

namespace eLife\Search\KeyValueStore;

interface KeyValueStore
{
    const NO_DEFAULT = 'no-default';

    public function setup();

    public function store(string $key, array $value);

    public function load(string $key, $default = self::NO_DEFAULT) : array;
}
