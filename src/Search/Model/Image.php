<?php

namespace eLife\Search\Model;

use LogicException;

final class Image
{
    private $scaled;
    private $domain;
    private $path;
    private $width;
    private $height;

    private function __construct(string $domain, string $path, bool $scaled = null, int $width = null, int $height = null)
    {
        $this->scaled = $scaled;
        $this->domain = $domain;
        $this->path = $path;
        $this->width = $width;
        $this->height = $height;
    }

    public static function basic(string $domain, string $path) : self
    {
        return new static (
            $domain,
            $path
        );
    }

    public static function scaled(string $domain, string $path, int $width) : self
    {
        return new static(
            $domain,
            $path,
            true,
            $width
        );
    }

    public static function cropped(string $domain, string $path, int $height, int $width) : self
    {
        return new static(
            $domain,
            $path,
            false,
            $width,
            $height
        );
    }

    public static function fromUrl(string $image) : self
    {
        $pieces = explode('/', $image);
        // https: / http:
        array_shift($pieces);
        // Empty space.
        array_shift($pieces);
        // Domain
        $domain = array_shift($pieces);
        // Type
        $type = array_shift($pieces);
        if ($type === 'fit') {
            // extra type (c)
            array_shift($pieces);
            // height
            array_shift($pieces);
        }
        // width
        array_shift($pieces);
        // Final result.
        return new static(
            $domain,
            implode('/', $pieces)
        );
    }

    public function getDomain() : string
    {
        return $this->domain;
    }

    public function getPath() : string
    {
        return $this->path;
    }

    public function scale(int $width) : self
    {
        return new static(
            $this->domain,
            $this->path,
            true,
            $width
        );
    }

    public function crop(int $width, int $height = null) : self
    {
        if (null === $height) {
            $height = $width;
        }

        return new static(
            $this->domain,
            $this->path,
            false,
            $width,
            $height
        );
    }

    public function __toString() : string
    {
        if (null === $this->scaled) {
            throw new LogicException('
                You must used a scaled or cropped version of this image before using it.
                ($image->crop($height, $width) -or- $image->scale($width))
            ');
        }
        if ($this->scaled) {
            return 'https://'.$this->domain.'/max/'.$this->width.'/'.$this->path;
        }

        return 'https://'.$this->domain.'/fit/c/'.$this->width.'/'.$this->height.'/'.$this->path;
    }
}
