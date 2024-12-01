<?php

declare(strict_types=1);

namespace Clotho\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD | Attribute::TARGET_FUNCTION | Attribute::IS_REPEATABLE)]
class EventAfter
{
    public function __construct(
        private ?string $eventName = null,
        private int $priority = 0
    ) {
    }

    public function getEventName(): ?string
    {
        return $this->eventName;
    }

    public function getPriority(): int
    {
        return $this->priority;
    }
}
