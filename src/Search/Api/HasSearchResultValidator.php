<?php

namespace eLife\Search\Api;

interface HasSearchResultValidator
{
    public function validateSearchResult($result, $strict) : bool;
}