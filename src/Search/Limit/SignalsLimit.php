<?php

namespace eLife\Search\Limit;

class SignalsLimit implements Limit
{
    private static $validSignals = [
        'SIGINT' => SIGINT,
        'SIGTERM' => SIGTERM,
        'SIGHUP' => SIGHUP,
    ];

    private $valid;
    private $reasons;

    public function __construct($signals)
    {
        foreach ($signals as $signal) {
            pcntl_signal(self::$validSignals[$signal], function () use ($signal) {
                $this->onTermination($signal);
            });
        }
    }

    public function onTermination($signal)
    {
        $this->reasons[] = "Received signal: $signal";
        $this->valid = false;
    }

    public static function stopOn(array $signals): self
    {
        return new static($signals);
    }

    public function __invoke(): bool
    {
        pcntl_signal_dispatch();

        return $this->valid === false;
    }

    public function getReasons(): array
    {
        return $this->reasons;
    }
}
