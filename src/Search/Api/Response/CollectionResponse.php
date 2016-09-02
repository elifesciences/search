<?php

namespace eLife\Search\Api\Response;

use eLife\Search\Api\Response\Common\Image;
use eLife\Search\Api\Response\Common\SnippetFields;
use eLife\Search\Api\Response\Common\Subjects;
use JMS\Serializer\Annotation\Accessor;
use JMS\Serializer\Annotation\SerializedName;
use JMS\Serializer\Annotation\Since;
use JMS\Serializer\Annotation\Type;

class CollectionResponse implements SearchResult
{
    use SnippetFields;
    use Subjects;
    use Image;

    /**
     * @Type("DateTime<'Y-m-d\TH:i:sO'>")
     * @Since(version="1")
     */
    public $updated;

    /**
     * @Type(eLife\Search\Api\Response\SelectedCuratorResponse::class)
     * @Since(version="1")
     * @SerializedName("selectedCurator")
     */
    public $selectedCurator;

    /**
     * @Type("string")
     * @Since(version="1")
     * @Accessor(getter="getType")
     */
    public $type = 'collection';
}
