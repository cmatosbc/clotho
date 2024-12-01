<?php

declare(strict_types=1);

namespace Clotho\Event;

use Clotho\Event\Event;
use Psr\EventDispatcher\StoppableEventInterface;

final class AfterMethodEvent extends Event implements StoppableEventInterface
{
    private bool $propagationStopped = false;

    public function __construct(
        private object $object,
        private string $method,
        private array $arguments,
        private mixed $result = null,
        private ?\Throwable $exception = null
    ) {}

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
