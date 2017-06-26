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
    const WRITE = 'write';
    const READ = 'read';

    /**
     * @return self
     */
    public static function fromFile(string $filename)
    {
        $contents = json_decode(file_get_contents($filename), true);
        Assertion::keyExists($contents, self::WRITE);
        Assertion::keyExists($contents, self::READ);

        return new self($contents[self::WRITE], $contents[self::READ]);
    }

    /**
     * @return self
     */
    public static function fromVersions(string $write, string $read)
    {
        return new self(json_decode(file_get_contents($filename)));
    }

    public function __construct(string $write, string $read)
    {
        $this->write = $write;
        $this->read = $read;
    }

    public function switchWrite(string $indexName)
    {
        return new self($indexName, $this->read);
    }

    public function switchRead(string $indexName)
    {
        return new self($this->write, $indexName);
    }

    public function operation($operation)
    {
        Assertion::propertyExists($this, $operation);

        return $this->$operation;
    }

    public function __toString()
    {
        return json_encode([
            self::WRITE => $this->write,
            self::READ => $this->read,
        ]);
    }

    public function toFile(string $filename)
    {
        file_put_contents($filename, $this->__toString());

        return $this;
    }
}
