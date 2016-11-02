<?php

namespace tests\eLife\Search;

use Exception;
use Psr\Log\NullLogger;

class ExceptionNullLogger extends NullLogger
{
    public function alert($message, array $context = [])
    {
        throw new Exception($message);
    }
}
