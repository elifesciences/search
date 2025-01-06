<?php

namespace eLife\Search\Api;

interface HasHeaders
{
    /** @return array<string, string> **/
    public function getHeaders(?int $version = null) : array;
}
