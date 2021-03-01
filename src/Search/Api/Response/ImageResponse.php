<?php

namespace eLife\Search\Api\Response;

use JMS\Serializer\Annotation\Since;
use JMS\Serializer\Annotation\Type;

final class ImageResponse
{
    /**
     * @Type(IiifImageResponse::class)
     * @Since(version="1")
     */
    public $banner;

    /**
     * @Type(IiifImageResponse::class)
     * @Since(version="1")
     */
    public $thumbnail;

    /**
     * @Type(IiifImageResponse::class)
     * @Since(version="1")
     */
    public $social;

    public function https()
    {
        $this->banner = $this->banner ? $this->banner->https() : null;
        $this->thumbnail = $this->thumbnail ? $this->thumbnail->https() : null;
        $this->social = $this->social ? $this->social->https() : null;

        return $this;
    }
}
