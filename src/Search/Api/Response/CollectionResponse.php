<?php

namespace eLife\Search\Api\Response;


class CollectionResponse implements SearchResult
{

    public function getType() : string
    {
        return 'collection';
    }
}
