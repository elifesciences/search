<?php

namespace tests\eLife\Search\Api\Elasticsearch;

use eLife\ApiSdk\Client\Articles;
use tests\eLife\Search\RamlRequirement;

final class ArticleElasticsearchTest extends ElasticsearchTestCase
{
    use RamlRequirement;

    public static function jsonProvider() : array
    {
        return [
            [static::getFixtureWithType('article-vor/v'.Articles::VERSION_ARTICLE_VOR.'/minimum.json', 'research-article')],
            [static::getFixtureWithType('article-vor/v'.Articles::VERSION_ARTICLE_VOR.'/complete.json', 'research-article')],
            [static::getFixtureWithType('article-poa/v'.Articles::VERSION_ARTICLE_POA.'/minimum.json', 'research-article')],
            [static::getFixtureWithType('article-poa/v'.Articles::VERSION_ARTICLE_POA.'/complete.json', 'research-article')],
        ];
    }
}
