<?php

namespace eLife\Search\Api\Response\ArticleResponse;

use eLife\Search\Api\Response\Common\Image;
use eLife\Search\Api\Response\Common\Published;
use eLife\Search\Api\Response\Common\SnippetFields;
use eLife\Search\Api\Response\Common\Subjects;
use JMS\Serializer\Annotation\Accessor;
use JMS\Serializer\Annotation\SerializedName;
use JMS\Serializer\Annotation\Since;
use JMS\Serializer\Annotation\Type;

trait Article
{
    use Published;
    use Subjects;
    use Image;
    use SnippetFields;

    /**
     * @Type("DateTimeImmutable<'Y-m-d\TH:i:s\Z'>")
     * @Since(version="1")
     * @SerializedName("statusDate")
     */
    public $statusDate;

    /**
     * @Type("integer")
     * @Since(version="1")
     */
    public $volume;

    /**
     * @Type("integer")
     * @Since(version="1")
     */
    public $version;

    /**
     * @Type("DateTimeImmutable<'Y-m-d\TH:i:s\Z'>")
     * @Since(version="1")
     * @SerializedName("versionDate")
     */
    public $versionDate;

    /**
     * @Type("integer")
     * @Since(version="1")
     */
    public $issue;

    /**
     * @Type("string")
     * @Since(version="1")
     * @SerializedName("titlePrefix")
     */
    public $titlePrefix;

    /**
     * @Type("string")
     * @Since(version="1")
     */
    public $stage;

    /**
     * @Type("string")
     * @Since(version="1")
     * @SerializedName("elocationId")
     */
    public $elocationId;

    /**
     * @Type("string")
     * @Since(version="1")
     */
    public $doi;

    /**
     * @Type("string")
     * @Since(version="1")
     * @SerializedName("authorLine")
     */
    public $authorLine;

    /**
     * @Type("string")
     * @Since(version="1")
     */
    public $pdf;

    /**
     * @Type("string")
     * @Since(version="1")
     * @Accessor(getter="getType")
     */
    public $type;
}
