<?php
namespace eLife\Search\Indexer;

use eLife\ApiSdk\Model\Model;

interface ModelIndexer
{
    public function prepareChangeSet(Model $model): ChangeSet;
}
