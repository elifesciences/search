<?php

namespace eLife\Search\Api\Response;

use JMS\Serializer\Annotation\SerializedName;
use JMS\Serializer\Annotation\Since;
use JMS\Serializer\Annotation\Type;

final class IiifImageResponse
{
    /**
     * @Type("string")
     * @Since(version="1")
     */
    public $alt;

    /**
     * @Type("string")
     * @Since(version="1")
     */
    public $uri;

    /**
     * @Type("array<string, string>")
     * @Since(version="1")
     */
    public $source;

    /**
     * @Type("array<string, integer>")
     * @Since(version="1")
     */
    public $size;

    /**
     * @Type("array<string, integer>")
     * @Since(version="1")
     * @SerializedName("focalPoint")
     */
    public $focalPoint;

    /**
     * @Type("array<string>")
     * @Since(version="1")
     */
    public $attribution;

    public function https()
    {
        return new static($this->alt, $this->makeHttps($this->uri), $this->source, $this->size, $this->focalPoint, $this->attribution);
    }

    private function makeHttps($uri)
    {
        return str_replace(['http:/', 'internal_elife_dummy_api'], ['https:/', 'internalelifedummyapi.com'], $uri);
    }

    public function __construct(string $alt, string $uri, array $source, array $size, array $focalPoint = null, $attribution = null)
    {
        $this->alt = $alt;
        $this->uri = $uri;
        $this->source = $source;
        $this->size = $size;
        $this->focalPoint = $focalPoint;
        $this->attribution = $attribution;
    }
}
