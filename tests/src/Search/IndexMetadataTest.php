<?php

namespace test\eLife\Search;

use eLife\Search\IndexMetadata;

class IndexMetadataTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->sample = IndexMetadata::fromContents('search_2', 'search_1', '20000101000000');
    }

    /**
     * @test
     */
    public function dumps_to_a_document_and_back()
    {
        $this->assertEquals(
            $this->sample,
            IndexMetadata::fromDocument($this->sample->toDocument())
        );
    }

    /**
     * @test
     */
    public function dumps_to_a_file_and_back()
    {
        $file = sys_get_temp_dir().'/'.uniqid().'.json';
        $this->sample->toFile($file);
        $this->assertEquals(
            $this->sample,
            IndexMetadata::fromFile($file)
        );
    }
}
