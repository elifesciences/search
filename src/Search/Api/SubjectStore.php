<?php

namespace eLife\Search\Api;

use Doctrine\Common\Cache\Cache;
use eLife\ApiSdk\ApiSdk;
use eLife\ApiSdk\Model\Subject;

final class SubjectStore
{
    private $serializer;
    private $cache;
    private $timeout;

    public function __construct(ApiSdk $sdk, Cache $cache, int $timeout = 3600)
    {
        $this->serializer = $sdk->getSerializer();
        $this->sdk = $sdk;
        $this->cache = $cache;
        $this->timeout = $timeout;
    }

    protected function saveSubjects(array $subjects) : bool
    {
        return $this->cache->save('search.subjects', $subjects, $this->timeout);
    }

    protected function mapSubjects($subject) : Subject
    {
        return $this->serializer->deserialize($subject, Subject::class, 'json');
    }

    protected function getSubjectsFromCache() : array
    {
        $subjects = $this->cache->fetch('search.subjects');
        if ($subjects) {
            return array_map([$this, 'mapSubjects'], $subjects);
        }

        return null;
    }

    protected function getSubjectsFromApi() : array
    {
        $subjects = [];
        foreach ($this->sdk->subjects() as $subject) {
            if ($subject instanceof Subject) {
                $subjects[] = $this->serializer->serialize($subject, 'json');
            }
        }

        return $subjects;
    }

    public function getSubjects() : array
    {
        if ($subjects = $this->getSubjectsFromCache()) {
            return $subjects;
        }
        $subjects = $this->getSubjectsFromApi();
        $this->saveSubjects($subjects);

        return $subjects;
    }
}
