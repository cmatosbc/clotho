<?php

declare(strict_types=1);

namespace Clotho\Tests\Unit;

use Clotho\Attribute\EventBefore;
use Clotho\Event\BeforeMethodEvent;
use Clotho\Event\EventDispatcher;
use Clotho\EventAttributeHandler;
use Clotho\Exception\EventDispatchException;
use Clotho\Exception\EventListenerException;
use PHPUnit\Framework\TestCase;

class EventExceptionTest extends TestCase
{
    private EventDispatcher $dispatcher;
    private EventAttributeHandler $handler;

    protected function setUp(): void
    {
        $this->dispatcher = new EventDispatcher();
        $this->handler = new EventAttributeHandler($this->dispatcher);
    }

    public function testEmptyEventNameThrowsException(): void
    {
        $this->expectException(EventDispatchException::class);
        $this->expectExceptionMessage('Invalid event name: ""');
        
        $this->dispatcher->dispatch('');
    }

    public function testInvalidListenerPriorityThrowsException(): void
    {
        $this->expectException(EventListenerException::class);
        $this->expectExceptionMessage('Invalid event listener priority: 150');
        
        $this->dispatcher->addEventListener('test.event', fn() => null, 150);
    }

    public function testNegativeInvalidListenerPriorityThrowsException(): void
    {
        $this->expectException(EventListenerException::class);
        $this->expectExceptionMessage('Invalid event listener priority: -150');
        
        $this->dispatcher->addEventListener('test.event', fn() => null, -150);
    }

    public function testListenerErrorWrappedInDispatchException(): void
    {
        $this->dispatcher->addEventListener(BeforeMethodEvent::class, function() {
            throw new \RuntimeException('Test error');
        });

        $this->expectException(EventDispatchException::class);
        $this->expectExceptionMessage('Error dispatching event "Clotho\Event\BeforeMethodEvent": Test error');

        $event = new BeforeMethodEvent(new \stdClass(), 'testMethod', []);
        $this->dispatcher->dispatch($event);
    }

    public function testEventExceptionsArePropagatedAsIs(): void
    {
        $this->dispatcher->addEventListener(BeforeMethodEvent::class, function() {
            throw EventDispatchException::invalidEventName('test');
        });

        $this->expectException(EventDispatchException::class);
        $this->expectExceptionMessage('Invalid event name: "test"');

        $event = new BeforeMethodEvent(new \stdClass(), 'testMethod', []);
        $this->dispatcher->dispatch($event);
    }
}
