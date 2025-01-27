<?php

namespace tests\eLife\Search\Api\Elasticsearch;

use eLife\ApiSdk\Client\LabsPosts;
use tests\eLife\Search\RamlRequirement;

final class LabsPostElasticsearchTest extends ElasticsearchTestCase
{
    use RamlRequirement;

    public static function jsonProvider() : array
    {
        return [
            [static::getFixtureWithType('labs-post/v'.LabsPosts::VERSION_POST.'/minimum.json', 'labs-post')],
            [static::getFixtureWithType('labs-post/v'.LabsPosts::VERSION_POST.'/complete.json', 'labs-post')],
        ];
    }
}
