<?php

namespace eLife\Search\Api;

interface HasHeaders
{
    public function getHeaders(?int $version = null) : array;
}
