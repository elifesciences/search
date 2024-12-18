<?php
namespace eLife\Search\Indexer;

use RuntimeException;
use Throwable;

class DocumentPostValidationFailure extends RuntimeException
{
    public function __construct(private array $document, $message, $code = null, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    public function getDocument() : array
    {
        return $this->document;
    }
}
