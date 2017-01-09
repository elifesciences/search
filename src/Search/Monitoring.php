<?php

namespace eLife\Search;

use RuntimeException;
use Throwable;

/**
 * Backed by the newrelic PHP extension,
 * gracefully degrades to no-op when it's not present.
 */
class Monitoring
{
    private $extension;
    private $appName;

    public function __construct()
    {
        $this->extension = extension_loaded('newrelic');
        if ($this->extension) {
            $appName = ini_get('newrelic.appname');
            if (!$appName) {
                throw new RuntimeException('newrelic.appname must be configured in a PHP *.ini file');
            }
            $this->appName = $appName;
        }
    }

    public function markAsBackground()
    {
        if ($this->extension) {
            newrelic_background_job();
        }
    }

    public function nameTransaction($name)
    {
        if ($this->extension) {
            newrelic_name_transaction($name);
        }
    }

    public function startTransaction()
    {
        if ($this->extension) {
            newrelic_start_transaction($this->appName);
        }
    }

    public function endTransaction()
    {
        if ($this->extension) {
            newrelic_end_transaction();
        }
    }

    public function recordException(Throwable $exception, $message = null)
    {
        if ($this->extension) {
            if ($message === null) {
                $message = $exception->getMessage();
            }
            newrelic_notice_error($message, $exception);
        }
    }
}
