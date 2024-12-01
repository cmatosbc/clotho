<?php

declare(strict_types=1);

namespace Clotho\Exception;

class EventPropagationException extends EventException
{
    public static function propagationStopped(string $eventName): self
    {
        return new self(sprintf('Event propagation stopped for "%s"', $eventName));
    }

    public static function alreadyStopped(string $eventName): self
    {
        return new self(sprintf('Event "%s" propagation was already stopped', $eventName));
    }
}
