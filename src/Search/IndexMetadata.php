<?php

namespace eLife\Search;

use Assert\Assertion;

final class IndexMetadata
{
    private $write;
    private $read;
    const WRITE = 'write';
    const READ = 'read';

    public static function fromFile(string $filename) : IndexMetadata
    {
        $document = json_decode(file_get_contents($filename), true);

        return self::fromDocument($document);
    }

    public static function fromDocument(array $document)
    {
        Assertion::keyExists($document, self::WRITE);
        Assertion::keyExists($document, self::READ);

        return new self(
            $document[self::WRITE],
            $document[self::READ]
        );
    }

    public static function fromContents(string $write, string $read) : IndexMetadata
    {
        return new self($write, $read);
    }

    private function __construct(string $write, string $read)
    {
        $this->write = $write;
        $this->read = $read;
    }

    public function switchWrite(string $indexName) : IndexMetadata
    {
        return new self($indexName, $this->read);
    }

    public function switchRead(string $indexName) : IndexMetadata
    {
        return new self($this->write, $indexName);
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

    public function toDocument()
    {
        return [
            self::WRITE => $this->write,
            self::READ => $this->read,
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
