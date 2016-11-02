<?php

namespace eLife\Search\Api\Response;

use Assert\Assertion;
use JMS\Serializer\Annotation\Since;
use JMS\Serializer\Annotation\Type;

final class ImageThumbnailResponse implements ImageVariant
{
    /**
     * @Type("string")
     * @Since(version="1")
     */
    public $alt;

    /**
     * @Type("array<string, array<string,string>>")
     * @Since(version="1")
     */
    public $sizes;

    public function https()
    {
        $sizes = $this->makeHttps($this->sizes);

        return new static(
            $this->alt, $sizes
        );
    }

    private function makeHttps($urls)
    {
        $sizes = [];
        foreach ($urls as $url) {
            foreach ($url as $k => $size) {
                //                $sizes[$k] = str_replace(['http:/', 'internal_elife_dummy_api'], ['https:/', 'internal_elife_dummy_api.com'], $size);
                $sizes[$k] = 'https://www.wat.com/image/'.$k.'.jpg';
            }
        }

        return $sizes;
    }

    public function __construct(string $alt, array $images)
    {
        Assertion::allInArray(array_flip($images), [250, 500, 70, 140], 'You need to provide all available sizes for this image');

        $this->alt = $alt;
        $this->sizes = [
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
