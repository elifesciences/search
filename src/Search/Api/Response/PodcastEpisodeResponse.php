<?php

namespace eLife\Search\Api\Response;

use eLife\Search\Api\Response\Common\Image;
use eLife\Search\Api\Response\Common\Published;
use eLife\Search\Api\Response\Common\SnippetFields;
use eLife\Search\Api\Response\Common\Subjects;
use JMS\Serializer\Annotation\Accessor;
use JMS\Serializer\Annotation\Groups;
use JMS\Serializer\Annotation\Since;
use JMS\Serializer\Annotation\Type;

class PodcastEpisodeResponse implements SearchResult
{
    use SnippetFields;
    use Subjects;
    use Published;
    use Image;

    /**
     * @Type("integer")
     * @Since(version="1")
     * @Groups({"full", "snippet"})
     */
    public $number;

    /**
     * @Type("string")
     * @Since(version="1")
     * @Groups({"full", "snippet"})
     */
    public $mp3;

    /**
     * @Type("string")
     * @Since(version="1")
     * @Groups({"snippet"})
     * @Accessor(getter="getType")
     */
    public $type = 'podcast-episode';
}
