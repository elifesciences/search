<?php

namespace tests\eLife\Search\Api\Elasticsearch;

use eLife\ApiSdk\Client\ReviewedPreprints;
use tests\eLife\Search\RamlRequirement;

final class ReviewedPreprintElasticsearchTest extends ElasticsearchTestCase
{
    use RamlRequirement;

    public static function jsonProvider() : array
    {
        return [
            [static::getFixtureWithType('reviewed-preprint/v'.ReviewedPreprints::VERSION_REVIEWED_PREPRINT.'/minimum.json', 'reviewed-preprint')],
            [static::getFixtureWithType('reviewed-preprint/v'.ReviewedPreprints::VERSION_REVIEWED_PREPRINT.'/complete.json', 'reviewed-preprint')],
        ];
    }
}
