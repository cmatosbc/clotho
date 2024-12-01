<?php

declare(strict_types=1);

namespace Clotho\Event;

use Clotho\Event\Event;

class AfterFunctionEvent extends Event
{
    public function __construct(
        private string $function,
        private array $arguments,
        private mixed $result = null,
        private ?\Throwable $exception = null
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

    public function getResult(): mixed
    {
        return $this->result;
    }

    public function getException(): ?\Throwable
    {
        return $this->exception;
    }
}
