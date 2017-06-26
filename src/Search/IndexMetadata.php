<?php

namespace eLife\Search;

use Assert\Assertion;

final class IndexMetadata
{
    /**
     * @var string
     */
    private $write;
    /**
     * @var string
     */
    private $read;
    /**
     * @var string
     */
    private $lastImport;
    const WRITE = 'write';
    const READ = 'read';
    const LAST_IMPORT = 'last_import';

    /**
     * @return self
     */
    public static function fromFile(string $filename)
    {
        $contents = json_decode(file_get_contents($filename), true);
        Assertion::keyExists($contents, self::WRITE);
        Assertion::keyExists($contents, self::READ);
        if (!array_key_exists(self::LAST_IMPORT, $contents)) {
            $contents[self::LAST_IMPORT] = '19700101000000';
        }

        return new self($contents[self::WRITE], $contents[self::READ], $contents[self::LAST_IMPORT]);
    }

    /**
     * @return self
     */
    public static function fromContents(string $write, string $read, string $lastImport = '19700101000000')
    {
        return new self($write, $read, $lastImport);
    }

    private function __construct(string $write, string $read, string $lastImport = '19700101000000')
    {
        $this->write = $write;
        $this->read = $read;
        $this->lastImport = $lastImport;
    }

    public function switchWrite(string $indexName)
    {
        return new self($indexName, $this->read, $this->lastImport);
    }

    public function switchRead(string $indexName)
    {
        return new self($this->write, $indexName, $this->lastImport);
    }

    public function updateLastImport(string $lastImport)
    {
        return new self($this->write, $this->read, $lastImport);
    }

    public function operation($operation)
    {
        Assertion::propertyExists($this, $operation);

        return $this->$operation;
    }

    public function write()
    {
        return $this->write;
    }

    public function read()
    {
        return $this->read;
    }

    public function lastImport()
    {
        return $this->lastImport;
    }

    public function __toString()
    {
        return json_encode([
            self::WRITE => $this->write,
            self::READ => $this->read,
            self::LAST_IMPORT => $this->lastImport,
        ]);
    }

    public function toFile(string $filename)
    {
        file_put_contents($filename, $this->__toString());

        return $this;
    }
}
