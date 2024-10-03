<?php

namespace eLife\Search\Indexer;

class ChangeSet
{
    private array $inserts = [];
    private array $deletes = [];

    public function addInsert(string $id, string $json)
    {
        $this->inserts[] = [
            'id' => $id,
            'json' => $json,
        ];
    }

    public function addDelete(string $id)
    {
        $this->deletes[] = $id;
    }

    public function getInserts() : array
    {
        return $this->inserts;
    }

    public function getDeletes() : array
    {
        return $this->deletes;
    }
}
