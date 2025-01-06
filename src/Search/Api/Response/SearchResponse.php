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
    public int $total;

    /**
     * @Type("array")
     * @Since(version="1")
     * @Accessor(setter="setItems")
     * @var array<mixed>
     */
    public array $items = [];

    /**
     * @Type(TypesResponse::class)
     * @Since(version="1")
     */
    public TypesResponse $types;

    /**
     * @Type("array")
     * @Since(version="1")
     * @var array<array{id: string, name: string, results: int}>
     */
    public array $subjects = [
        [
            'id' => 'biophysics-structural-biology',
            'name' => 'Biophysics and Structural Biology',
            'results' => 1,
        ],
    ];

    /**
     * @param array<int, mixed> $items
     * @param array<array{id: string, name: string, results: int}> $subjects
     */
    public function __construct(array $items, int $total, array $subjects, TypesResponse $types)
    {
        $this->items = $items;
        $this->total = $total;
        $this->types = $types;
        $this->subjects = $subjects;
    }

    /**
     * @param array<string, mixed> $items
     */
    public function setItems(array $items): void
    {
        $this->total = count($items);
        $this->items = $items;
        $this->types = TypesResponse::fromList($this->items);
    }

    public function getHeaders(?int $version = 2) : array
    {
        return [
            'Content-Type' => "application/vnd.elife.search+json; version=$version",
        ];
    }
}
