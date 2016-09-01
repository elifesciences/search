<?php

namespace eLife\Search\Api\Response;


class EventResponse implements SearchResult
{

    public function getType() : string
    {
        return 'event';
    }
}
