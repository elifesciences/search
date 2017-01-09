<?php

namespace eLife\Search\Limit;

class MemoryLimit implements Limit
{
    private $bytes;
    private $actualBytes;

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
        $this->actualBytes = memory_get_usage(true);
        if ($this->actualBytes > $this->bytes) {
            return true;
        }

        return false;
    }

    public function getReasons() : array
    {
        return ["Memory limit exceeded: {$this->bytes} bytes exceeds {$this->actualBytes} bytes"];
    }
}
