<?php

namespace tests\eLife\Search\Api\Elasticsearch;

use tests\eLife\Search\RamlRequirement;

final class ArticleElasticsearchTest extends ElasticsearchTestCase
{
    use RamlRequirement;

    public static function jsonProvider() : array
    {
        return [
            [static::getFixtureWithType('article-vor/v8/minimum.json', 'research-article')],
            [static::getFixtureWithType('article-vor/v8/complete.json', 'research-article')],
            [static::getFixtureWithType('article-poa/v3/minimum.json', 'research-article')],
            [static::getFixtureWithType('article-poa/v3/complete.json', 'research-article')],
        ];
    }
}
