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

    public function __construct($signals)
    {
        foreach ($signals as $signal) {
            pcntl_signal(self::$validSignals[$signal], [$this, 'onTermination']);
        }
    }

    public function onTermination()
    {
        $this->valid = false;
    }

    public static function stopOn(array $signals) : self
    {
        return new static($signals);
    }

    public function __invoke() : bool
    {
        pcntl_signal_dispatch();

        return $this->valid === false;
    }

    public function getReasons(): array
    {
        return ['Signals: Stopped by user'];
    }
}
