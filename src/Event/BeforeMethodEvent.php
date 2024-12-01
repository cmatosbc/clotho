<?php

declare(strict_types=1);

namespace Clotho\Event;

use Psr\EventDispatcher\StoppableEventInterface;

final class BeforeMethodEvent implements StoppableEventInterface
{
    private bool $propagationStopped = false;

    public function __construct(
        private object $object,
        private string $methodName,
        private array $arguments
    ) {}

    public function getObject(): object
    {
        return $this->object;
    }

    public function getMethodName(): string
    {
        return $this->methodName;
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
