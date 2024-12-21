<?php

namespace eLife\Search\Api\Elasticsearch\Response;

interface IsDocumentResponse
{
    /** @param array<string, mixed> $_source */
    public function setSource(array $_source): void;

    /** @return array<mixed> */
    public function unwrap() : array;
}
