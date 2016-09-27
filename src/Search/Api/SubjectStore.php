<?php

namespace eLife\Search\Api;

use eLife\ApiSdk\ApiSdk;
use Traversable;

final class SubjectStore
{
    private $subjects;

    public function __construct(ApiSdk $sdk)
    {
        $this->sdk = $sdk;
    }

    protected function saveSubjects(Traversable $subjects)
    {
        $this->subjects = $subjects;
    }

    protected function getSubjectsFromCache()
    {
        return $this->subjects;
    }

    protected function getSubjectsFromApi() : Traversable
    {
        return $this->sdk->subjects();
    }

    public function getSubjects() : Traversable
    {
        if ($subjects = $this->getSubjectsFromCache()) {
            return $subjects;
        }
        $subjects = $this->getSubjectsFromApi();
        $this->saveSubjects($subjects);

        return $subjects;
    }
}
