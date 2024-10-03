<?php

namespace eLife\Search\Indexer\ModelIndexer\Helper;

use DateTimeImmutable;

trait SortDate
{
    public function addSortDate($object, DateTimeImmutable $date = null)
    {
        if ($date) {
            $object->sortDate = $date->format('Y-m-d\TH:i:s\Z');
        }
    }
}
