<?php

namespace eLife\Search\Api\Response;

use eLife\Search\Api\HasHeaders;
use JMS\Serializer\Annotation\Accessor;
use JMS\Serializer\Annotation\Since;
use JMS\Serializer\Annotation\Type;

final class SearchResponse implements HasHeaders
{
    /**
     * @Type("integer")
     * @Since(version="1")
     */
    public $total;

    /**
     * @Type("array<eLife\Search\Api\Response\SearchResult>")
     * @Since(version="1")
     * @Accessor(setter="setItems")
     */
    public $items = [];

    /**
     * @Type(TypesResponse::class)
     * @Since(version="1")
     */
    public $types;

    public function __construct(array $items = [])
    {
        $this->setItems($items);
    }

    public function setItems($items)
    {
        $this->total = count($items);
        $this->items = $items;
        $this->types = TypesResponse::fromList($this->items);
    }

    public function getHeaders($version = 1) : array
    {
        return [
            'Content-Type' => "application/vnd.elife.search+json;version=$version",
        ];
    }
}
