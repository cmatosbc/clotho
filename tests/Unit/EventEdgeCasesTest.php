<?php

declare(strict_types=1);

namespace Clotho\Tests\Unit;

use Clotho\Event\BeforeMethodEvent;
use Clotho\Event\EventDispatcher;
use Clotho\EventAttributeHandler;
use PHPUnit\Framework\TestCase;
use Psr\EventDispatcher\StoppableEventInterface;

class EventEdgeCasesTest extends TestCase
{
    private EventDispatcher $dispatcher;
    private EventAttributeHandler $handler;

    protected function setUp(): void
    {
        $this->dispatcher = new EventDispatcher();
        $this->handler = new EventAttributeHandler($this->dispatcher);
    }

    public function testDispatchWithNoListenersReturnsEvent(): void
    {
        $event = new BeforeMethodEvent(new \stdClass(), 'testMethod', []);
        $result = $this->dispatcher->dispatch($event);
        
        $this->assertSame($event, $result);
    }

    public function testDispatchWithNamedEventAndNoListenersReturnsPayload(): void
    {
        $payload = ['test' => 'data'];
        $result = $this->dispatcher->dispatch('test.event', $payload);
        
        $this->assertSame($payload, $result);
    }

    public function testAlreadyStoppedEventSkipsListeners(): void
    {
        $called = false;
        $this->dispatcher->addEventListener('test.event', function() use (&$called) {
            $called = true;
        });

        $event = new class implements StoppableEventInterface {
            public function isPropagationStopped(): bool
            {
                return true;
            }
        };

        $this->dispatcher->dispatch($event);
        $this->assertFalse($called, 'Listener should not be called for already stopped event');
    }

    public function testListenerModifyingPayload(): void
    {
        $this->dispatcher->addEventListener('test.event', function(array &$payload) {
            $payload['modified'] = true;
            return true;
        });

        $payload = ['original' => true];
        $result = $this->dispatcher->dispatch('test.event', $payload);

        $this->assertTrue($result['modified']);
        $this->assertTrue($result['original']);
    }

    public function testWildcardListenerWithComplexPattern(): void
    {
        $calls = [];
        $this->dispatcher->addEventListener('namespace.*.user.{created,updated}', function() use (&$calls) {
            $calls[] = func_get_args();
        });

        $this->dispatcher->dispatch('namespace.app.user.created', ['id' => 1]);
        $this->dispatcher->dispatch('namespace.web.user.updated', ['id' => 2]);
        $this->dispatcher->dispatch('namespace.other.product.created', ['id' => 3]);

        $this->assertCount(2, $calls);
        $this->assertEquals(['id' => 1], $calls[0][0]);
        $this->assertEquals(['id' => 2], $calls[1][0]);
    }

    public function testNestedEventDispatch(): void
    {
        $sequence = [];
        
        $this->dispatcher->addEventListener('outer.event', function() use (&$sequence) {
            $sequence[] = 'outer.start';
            $this->dispatcher->dispatch('inner.event');
            $sequence[] = 'outer.end';
        });

        $this->dispatcher->addEventListener('inner.event', function() use (&$sequence) {
            $sequence[] = 'inner';
        });

        $this->dispatcher->dispatch('outer.event');

        $this->assertEquals(['outer.start', 'inner', 'outer.end'], $sequence);
    }
}
