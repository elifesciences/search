<?php

namespace tests\eLife\Search\Api\Elasticsearch;

use tests\eLife\Search\RamlRequirement;

final class ArticleElasticsearchTest extends ElasticsearchTestCase
{
    use RamlRequirement;

    public function jsonProvider() : array
    {
        return [
            [$this->getFixtureWithType('article-vor/v7/minimum.json', 'research-article')],
            [$this->getFixtureWithType('article-vor/v7/complete.json', 'research-article')],
            [$this->getFixtureWithType('article-poa/v3/minimum.json', 'research-article')],
            [$this->getFixtureWithType('article-poa/v3/complete.json', 'research-article')],
        ];
    }
}
