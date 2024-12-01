<?php

declare(strict_types=1);

namespace Clotho\Event;

use Clotho\Event\Event;

final class BeforeFunctionEvent extends Event
{
    public function __construct(
        private string $function,
        private array $arguments
    ) {
    }

    public function getFunction(): string
    {
        return $this->function;
    }

    public function getArguments(): array
    {
        return $this->arguments;
    }
}
