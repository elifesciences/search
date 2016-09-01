<?php

namespace eLife\Search\Api\Response;


class SubjectResponse implements SearchResult
{


    public function getType() : string
    {
        return 'subject';
    }
}
