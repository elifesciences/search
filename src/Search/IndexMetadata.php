<?php

namespace eLife\Search;

use Assert\Assertion;

final class IndexMetadata
{
    private $write;
    private $read;
    private $lastImport;
    const WRITE = 'write';
    const READ = 'read';
    const LAST_IMPORT = 'last_import';

    public static function fromFile(string $filename) : IndexMetadata
    {
        $document = json_decode(file_get_contents($filename), true);

        return self::fromDocument($document);
    }

    public static function fromDocument(array $document)
    {
        Assertion::keyExists($document, self::WRITE);
        Assertion::keyExists($document, self::READ);
        if (!array_key_exists(self::LAST_IMPORT, $document)) {
            $document[self::LAST_IMPORT] = '19700101000000';
        }

        return new self(
            $document[self::WRITE],
            $document[self::READ],
            $document[self::LAST_IMPORT]
        );
    }

    public static function fromContents(string $write, string $read, string $lastImport = '19700101000000') : IndexMetadata
    {
        return new self($write, $read, $lastImport);
    }

    private function __construct(string $write, string $read, string $lastImport = '19700101000000')
    {
        $this->write = $write;
        $this->read = $read;
        $this->lastImport = $lastImport;
    }

    public function switchWrite(string $indexName) : IndexMetadata
    {
        return new self($indexName, $this->read, $this->lastImport);
    }

    public function switchRead(string $indexName) : IndexMetadata
    {
        return new self($this->write, $indexName, $this->lastImport);
    }

    public function updateLastImport(string $lastImport) : IndexMetadata
    {
        return new self($this->write, $this->read, $lastImport);
    }

    public function operation($operation) : string
    {
        Assertion::propertyExists($this, $operation);

        return $this->$operation;
    }

    public function write() : string
    {
        return $this->write;
    }

    public function read() : string
    {
        return $this->read;
    }

    public function lastImport() : string
    {
        return $this->lastImport;
    }

    public function toDocument()
    {
        return [
            self::WRITE => $this->write,
            self::READ => $this->read,
            self::LAST_IMPORT => $this->lastImport,
        ];
    }

    public function __toString() : string
    {
        return json_encode($this->toDocument());
    }

    public function toFile(string $filename) : IndexMetadata
    {
        file_put_contents($filename, $this->__toString());

        return $this;
    }
}
