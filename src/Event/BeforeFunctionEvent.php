<?php

declare(strict_types=1);

namespace Clotho\Event;

use Psr\EventDispatcher\StoppableEventInterface;

final class BeforeFunctionEvent implements StoppableEventInterface
{
    private bool $propagationStopped = false;

    public function __construct(
        private string $functionName,
        private array $arguments
    ) {}

    public function getFunctionName(): string
    {
        return $this->functionName;
    }

    public function getArguments(): array
    {
        return $this->arguments;
    }

    public function isPropagationStopped(): bool
    {
        return $this->propagationStopped;
    }

    public function stopPropagation(): void
    {
        $this->propagationStopped = true;
    }
}
