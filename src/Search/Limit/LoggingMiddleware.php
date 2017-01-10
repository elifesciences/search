<?php

namespace eLife\Search\Limit;

use Psr\Log\LoggerInterface;

class LoggingMiddleware implements Limit
{
    private $limit;
    private $logger;
    private $reasons;

    public function __construct(Limit $limit, LoggerInterface $logger)
    {
        $this->limit = $limit;
        $this->logger = $logger;
    }

    public function __invoke(): bool
    {
        $limit = $this->limit;
        $limitReached = $limit();
        if ($limitReached) {
            $this->reasons = $limit->getReasons();
            foreach ($this->reasons as $reason) {
                $this->logger->info($reason);
            }
        }

        return $limitReached;
    }

    public function getReasons(): array
    {
        return $this->reasons;
    }
}
