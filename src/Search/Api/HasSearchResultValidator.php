<?php

namespace eLife\Search\Api;

interface HasSearchResultValidator
{
    public function validateSearchResult(mixed $result, bool $strict) : bool;
}
