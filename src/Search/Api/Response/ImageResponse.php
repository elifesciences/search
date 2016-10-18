<?php

namespace eLife\Search\Api\Response;

use Assert\Assertion;
use JMS\Serializer\Annotation\Since;
use JMS\Serializer\Annotation\Type;

final class ImageResponse
{
    /**
     * @Type("string")
     * @Since(version="1")
     */
    public $alt;

    /**
     * @Type("array<string, array<integer,string>>")
     * @Since(version="1")
     */
    public $sizes;

    public function https()
    {
        return new static(
            $this->alt, $this->sizes ? $this->makeHttps($this->sizes) : []
        );
    }

    private function makeHttps($urls)
    {
        foreach ($urls as &$url) {
            foreach ($url as &$size) {
                $size = str_replace('http:/', 'https:', $size);
            }
        }

        return $urls;
    }

    public function __construct(string $alt, array $images)
    {
        Assertion::allKeyExists($images, [900, 1800, 250, 500, 70, 140]);

        $this->alt = $alt;
        $this->sizes = [
            '2:1' => [
                900 => $images[900],
                1800 => $images[1800],
            ],
            '16:9' => [
                250 => $images[250],
                500 => $images[500],
            ],
            '1:1' => [
                70 => $images[70],
                140 => $images[140],
            ],
        ];
    }
}
