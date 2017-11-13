<?php

namespace eLife\Search\KeyValueStore;

interface KeyValueStore
{
    public function setup();

    /**
     * @param mixed $value
     */
    public function store(string $key, array $value);

    public function load(string $key) : array;
}
