<?php

declare(strict_types=1);

namespace Clotho\Tests\Unit;

use Clotho\Attribute\EventAfter;
use Clotho\Attribute\EventBefore;
use Clotho\Event\AfterMethodEvent;
use Clotho\Event\BeforeMethodEvent;
use Clotho\Event\EventDispatcher;
use Clotho\EventAttributeHandler;
use PHPUnit\Framework\TestCase;

class EventPriorityTest extends TestCase
{
    private EventDispatcher $dispatcher;
    private EventAttributeHandler $handler;
    private array $executionOrder;

    protected function setUp(): void
    {
        $this->dispatcher = new EventDispatcher();
        $this->handler = new EventAttributeHandler($this->dispatcher);
        $this->executionOrder = [];
    }

    public function testListenerExecutionOrder(): void
    {
        // Add listeners with different priorities
        $this->dispatcher->addEventListener(BeforeMethodEvent::class, function () {
            $this->executionOrder[] = 'before_high';
        }, 10);

        $this->dispatcher->addEventListener(BeforeMethodEvent::class, function () {
            $this->executionOrder[] = 'before_low';
        }, 5);

        // Create test class with single event
        $testClass = new class {
            #[EventBefore]
            public function testMethod(): string
            {
                return 'test result';
            }
        };

        // Wrap and execute the method
        $wrappedMethod = $this->handler->wrapMethod($testClass, 'testMethod');
        $wrappedMethod();

        // Verify execution order
        $this->assertEquals(
            ['before_high', 'before_low'],
            $this->executionOrder,
            'Listeners should execute in priority order (highest to lowest)'
        );
    }

    public function testEventPropagationWithPriority(): void
    {
        $executed = [];

        // Add listeners with different priorities
        $this->dispatcher->addEventListener(BeforeMethodEvent::class, function (BeforeMethodEvent $event) use (&$executed) {
            $executed[] = 'high';
            $event->stopPropagation();
        }, 10);

        $this->dispatcher->addEventListener(BeforeMethodEvent::class, function () use (&$executed) {
            $executed[] = 'medium';
        }, 5);

        $testClass = new class {
            #[EventBefore]
            public function testMethod(): void
            {
            }
        };

        // Wrap and execute the method
        $wrappedMethod = $this->handler->wrapMethod($testClass, 'testMethod');
        $wrappedMethod();

        // Verify only high priority listener executed
        $this->assertEquals(['high'], $executed, 'Only high priority listener should execute before propagation stops');
    }

    public function testMultipleEventTypesWithPriority(): void
    {
        $executed = [];

        // Add before event listeners
        $this->dispatcher->addEventListener(BeforeMethodEvent::class, function () use (&$executed) {
            $executed[] = 'before_high';
        }, 10);

        $this->dispatcher->addEventListener(BeforeMethodEvent::class, function () use (&$executed) {
            $executed[] = 'before_low';
        }, 5);

        // Add after event listeners
        $this->dispatcher->addEventListener(AfterMethodEvent::class, function () use (&$executed) {
            $executed[] = 'after_high';
        }, 10);

        $this->dispatcher->addEventListener(AfterMethodEvent::class, function () use (&$executed) {
            $executed[] = 'after_low';
        }, 5);

        $testClass = new class {
            #[EventBefore]
            #[EventAfter]
            public function testMethod(): string
            {
                return 'test';
            }
        };

        // Wrap and execute the method
        $wrappedMethod = $this->handler->wrapMethod($testClass, 'testMethod');
        $wrappedMethod();

        // Verify execution order across event types
        $this->assertEquals(
            ['before_high', 'before_low', 'after_high', 'after_low'],
            $executed,
            'Events should execute in order: before (high to low) then after (high to low)'
        );
    }
}
