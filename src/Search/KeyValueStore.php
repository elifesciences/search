<?php

namespace eLife\Search;

interface KeyValueStore
{
    public function setup();

    /**
     * @param mixed $value
     * @return void
     */
    public function store(string $key, array $value);

    public function load(string $key) : array;
}
