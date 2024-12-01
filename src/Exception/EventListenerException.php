<?php

declare(strict_types=1);

namespace Clotho\Exception;

class EventListenerException extends EventException
{
    public static function invalidPriority(int $priority): self
    {
        return new self(sprintf('Invalid event listener priority: %d', $priority));
    }

    public static function listenerNotFound(string $eventName): self
    {
        return new self(sprintf('No listeners found for event "%s"', $eventName));
    }
}
