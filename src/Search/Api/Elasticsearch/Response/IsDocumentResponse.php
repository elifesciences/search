<?php

namespace eLife\Search\Api\Elasticsearch\Response;

interface IsDocumentResponse
{
    public function setSource(array $_source);
    public function unwrap() : array;
}
