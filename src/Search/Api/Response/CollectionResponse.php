<?php

namespace eLife\Search\Api\Response;

use eLife\Search\Api\Response\Common\Image;
use eLife\Search\Api\Response\Common\SnippetFields;
use JMS\Serializer\Annotation\Accessor;
use JMS\Serializer\Annotation\Groups;
use JMS\Serializer\Annotation\Since;
use JMS\Serializer\Annotation\Type;

class CollectionResponse implements SearchResult
{
    use SnippetFields;
    use Image;

    /**
     * @Type("DateTime<'c'>")
     * @Since(version="1")
     * @Groups({"snippet"})
     */
    public $updated;

    /**
     * @Type("eLife\Search\Api\Response\CuratorResponse")
     */
    public $selectedCurator;

    /**
     * @Type("string")
     * @Since(version="1")
     * @Groups({"snippet"})
     * @Accessor(getter="getType")
     */
    public $type = 'collection';
}
