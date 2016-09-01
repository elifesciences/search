<?php

namespace eLife\Search\Api\Response;

class InterviewResponse implements SearchResult
{
    public function getType() : string
    {
        return 'interview';
    }
}
