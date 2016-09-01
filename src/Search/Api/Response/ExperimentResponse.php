<?php

namespace eLife\Search\Api\Response;

class ExperimentResponse implements SearchResult
{
    public function getType() : string
    {
        return 'experiment';
    }
}
