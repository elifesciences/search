<?php

namespace eLife\Search\Api\Response;

use Assert\Assertion;
use eLife\Search\Model\Image;
use JMS\Serializer\Annotation\Type;

final class ImageResponse
{
    /**
     * @Type("string")
     */
    public $alt;

    /**
     * @Type("array<string, array<integer,string>>")
     */
    public $sizes;

    public function __construct(string $alt, array $images)
    {
        Assertion::allKeyExists($images, [ 900, 1800, 250, 500, 70, 140 ]);

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
