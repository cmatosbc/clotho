<?php

declare(strict_types=1);

namespace Clotho\Event;

use Clotho\Event\Event;

final class BeforeMethodEvent extends Event
{
    public function __construct(
        private object $object,
        private string $method,
        private array $arguments
    ) {
    }

    public function getObject(): object
    {
        return $this->object;
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function getArguments(): array
    {
        return $this->arguments;
    }
}
