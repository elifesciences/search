<?php

namespace eLife\Search\Api\Response;

use eLife\Search\Api\Response\Common\Image;
use eLife\Search\Api\Response\Common\Published;
use eLife\Search\Api\Response\Common\SnippetFields;
use eLife\Search\Api\Response\Common\Subjects;
use JMS\Serializer\Annotation\Accessor;
use JMS\Serializer\Annotation\Since;
use JMS\Serializer\Annotation\Type;

final class PodcastEpisodeResponse implements SearchResult
{
    use SnippetFields;
    use Subjects;
    use Published;
    use Image;

    /**
     * @Type("integer")
     * @Since(version="1")
     */
    public $number;

    /**
     * @Type("array<eLife\Search\Api\Response\SourcesResponse>")
     * @Since(version="1")
     */
    public $sources;

    /**
     * @Type("string")
     * @Since(version="1")
     * @Accessor(getter="getType")
     */
    public $type = 'podcast-episode';
}
