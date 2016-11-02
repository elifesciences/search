<?php

namespace eLife\Search\Api\Response;

use Assert\Assertion;
use JMS\Serializer\Annotation\Since;
use JMS\Serializer\Annotation\Type;

final class ImageBannerResponse implements ImageVariant
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
        Assertion::allInArray(array_flip($images), [900, 1800], 'You need to provide all available sizes for this image');

        $this->alt = $alt;
        $this->sizes = [
            '2:1' => [
                900 => $images[900],
                1800 => $images[1800],
            ],
        ];
    }
}
