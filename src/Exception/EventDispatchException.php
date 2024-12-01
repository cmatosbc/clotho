<?php

declare(strict_types=1);

namespace Clotho\Exception;

class EventDispatchException extends EventException
{
    public static function invalidEventName(string $eventName): self
    {
        return new self(sprintf('Invalid event name: "%s"', $eventName));
    }

    public static function dispatchError(string $eventName, string $message): self
    {
        return new self(sprintf('Error dispatching event "%s": %s', $eventName, $message));
    }
}
