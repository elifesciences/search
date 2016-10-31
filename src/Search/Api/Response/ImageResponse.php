<?php

namespace eLife\Search\Api\Response;

use JMS\Serializer\Annotation\Since;
use JMS\Serializer\Annotation\Type;

final class ImageResponse
{
    /**
     * @Type(ImageBannerResponse::class)
     * @Since(version="1")
     */
    public $banner;

    /**
     * @Type(ImageThumbnailResponse::class)
     * @Since(version="1")
     */
    public $thumbnail;
}
