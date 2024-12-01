<?php

declare(strict_types=1);

namespace Clotho\Event;

use Psr\EventDispatcher\StoppableEventInterface;

final class AfterFunctionEvent implements StoppableEventInterface
{
    private bool $propagationStopped = false;

    public function __construct(
        private string $functionName,
        private array $arguments,
        private mixed $result = null,
        private ?\Throwable $exception = null
    ) {}

    public function getFunctionName(): string
    {
        return $this->functionName;
    }

    public function getArguments(): array
    {
        return $this->arguments;
    }

    public function getResult(): mixed
    {
        return $this->result;
    }

    public function getException(): ?\Throwable
    {
        return $this->exception;
    }

    public function hasException(): bool
    {
        return $this->exception !== null;
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
