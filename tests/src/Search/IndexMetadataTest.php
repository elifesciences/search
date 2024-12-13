<?php

namespace test\eLife\Search;

use eLife\Search\IndexMetadata;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class IndexMetadataTest extends TestCase
{
    private IndexMetadata $sample;

    public function setUp(): void
    {
        $this->sample = IndexMetadata::fromContents('search_2', 'search_1');
    }

    #[Test]
    public function dumpsToADocumentAndBack()
    {
        $this->assertEquals(
            $this->sample,
            IndexMetadata::fromDocument($this->sample->toDocument())
        );
    }

    #[Test]
    public function dumpsToAFileAndBack()
    {
        $file = sys_get_temp_dir().'/'.uniqid().'.json';
        $this->sample->toFile($file);
        $this->assertEquals(
            $this->sample,
            IndexMetadata::fromFile($file)
        );
    }
}
