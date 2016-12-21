<?php
namespace eLife\Search\Annotation;

class MemoryLimit
{
    private $bytes;
    
    public static function mb($megabytes) : self
    {
        return new self($megabytes * 1024 * 1024);
    }
    
    private function __construct($bytes)
    {
        $this->bytes = $bytes;
    }

    public function __invoke() : bool
    {
        if (memory_get_usage(true) > $this->bytes) {
            return true;
        }
        return false;
    }
}
