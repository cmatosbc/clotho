<?php

declare(strict_types=1);

namespace Clotho\Tests\Unit;

use Clotho\Attribute\EventAfter;
use Clotho\Attribute\EventBefore;
use Clotho\Event\EventDispatcher;
use Clotho\EventAttributeHandler;
use PHPUnit\Framework\TestCase;

class EventAttributeTest extends TestCase
{
    private EventDispatcher $dispatcher;
    private EventAttributeHandler $handler;
    private array $eventLog = [];

    protected function setUp(): void
    {
        $this->dispatcher = new EventDispatcher();
        $this->handler = new EventAttributeHandler($this->dispatcher);
        $this->eventLog = [];
    }

    public function testVisibleExecutionOrder(): void
    {
        $beforeTriggered = false;
        $afterTriggered = false;
        $methodExecuted = false;

        $testClass = new class {
            #[EventBefore('visible.before')]
            #[EventAfter('visible.after')]
            public function processData(string $data): string
            {
                return strtoupper($data);
            }
        };

        $this->dispatcher->addEventListener('visible.before', function ($payload) use (&$beforeTriggered) {
            $beforeTriggered = true;
            echo "\n⚡ BEFORE EVENT TRIGGERED";
            echo "\n   Arguments: " . json_encode($payload['arguments']) . "\n";
        });

        $this->dispatcher->addEventListener('visible.after', function ($payload) use (&$afterTriggered) {
            $afterTriggered = true;
            echo "\n✨ AFTER EVENT TRIGGERED";
            echo "\n   Result: " . $payload['result'] . "\n";
        });

        echo "\n\n=== Starting Method Execution ===\n";
        
        $wrappedMethod = $this->handler->wrapMethod($testClass, 'processData');
        
        echo "\n🔄 EXECUTING METHOD with data: test data\n";
        $result = $wrappedMethod('test data');
        $methodExecuted = true;
        
        echo "\n=== Method Execution Completed ===";
        echo "\nFinal result: {$result}\n\n";

        $this->assertTrue($beforeTriggered, 'Before event was not triggered');
        $this->assertTrue($afterTriggered, 'After event was not triggered');
        $this->assertTrue($methodExecuted, 'Method was not executed');
        $this->assertEquals('TEST DATA', $result, 'Method result is incorrect');
    }

    public function testVisibleExceptionHandling(): void
    {
        $beforeTriggered = false;
        $afterTriggered = false;
        $exceptionCaught = false;

        $testClass = new class {
            #[EventBefore('exception.before')]
            #[EventAfter('exception.after')]
            public function riskyOperation(): void
            {
                throw new \RuntimeException('Something went wrong!');
            }
        };

        $this->dispatcher->addEventListener('exception.before', function () use (&$beforeTriggered) {
            $beforeTriggered = true;
            echo "\n⚡ BEFORE EVENT TRIGGERED\n";
        });

        $this->dispatcher->addEventListener('exception.after', function ($payload) use (&$afterTriggered) {
            $afterTriggered = true;
            echo "\n✨ AFTER EVENT TRIGGERED";
            echo "\n   Exception message: " . $payload['exception']->getMessage() . "\n";
        });

        echo "\n\n=== Starting Method Execution (with exception) ===\n";
        
        $wrappedMethod = $this->handler->wrapMethod($testClass, 'riskyOperation');
        
        try {
            echo "\n🔄 EXECUTING METHOD (will throw exception)\n";
            $wrappedMethod();
        } catch (\RuntimeException $e) {
            $exceptionCaught = true;
            echo "\n❌ Exception caught: " . $e->getMessage() . "\n";
        }
        
        echo "\n=== Method Execution Completed ===\n\n";

        $this->assertTrue($beforeTriggered, 'Before event was not triggered');
        $this->assertTrue($afterTriggered, 'After event was not triggered');
        $this->assertTrue($exceptionCaught, 'Exception was not caught');
    }

    public function testBeforeEventIsTriggered(): void
    {
        $triggered = false;
        $this->dispatcher->addEventListener('test.before', function ($payload) use (&$triggered) {
            $triggered = true;
            $this->assertArrayHasKey('arguments', $payload);
            $this->assertEquals(['test arg'], $payload['arguments']);
        });

        $testClass = new class {
            #[EventBefore('test.before')]
            public function testMethod(string $arg): string
            {
                return "Result: {$arg}";
            }
        };

        $wrappedMethod = $this->handler->wrapMethod($testClass, 'testMethod');
        $wrappedMethod('test arg');

        $this->assertTrue($triggered, 'Before event was not triggered');
    }

    public function testAfterEventIsTriggered(): void
    {
        $triggered = false;
        $this->dispatcher->addEventListener('test.after', function ($payload) use (&$triggered) {
            $triggered = true;
            $this->assertArrayHasKey('result', $payload);
            $this->assertEquals('Result: test arg', $payload['result']);
        });

        $testClass = new class {
            #[EventAfter('test.after')]
            public function testMethod(string $arg): string
            {
                return "Result: {$arg}";
            }
        };

        $wrappedMethod = $this->handler->wrapMethod($testClass, 'testMethod');
        $wrappedMethod('test arg');

        $this->assertTrue($triggered, 'After event was not triggered');
    }

    public function testEventExecutionOrder(): void
    {
        $testClass = new class {
            #[EventBefore('order.before')]
            #[EventAfter('order.after')]
            public function testMethod(string $arg): string
            {
                return "Result: {$arg}";
            }
        };

        $this->dispatcher->addEventListener('order.before', function () {
            $this->eventLog[] = 'before';
        });

        $this->dispatcher->addEventListener('order.after', function () {
            $this->eventLog[] = 'after';
        });

        $wrappedMethod = $this->handler->wrapMethod($testClass, 'testMethod');
        $wrappedMethod('test');

        $this->assertEquals(['before', 'after'], $this->eventLog, 'Events were not triggered in the correct order');
    }

    public function testAfterEventReceivesMethodResult(): void
    {
        $expectedResult = null;
        
        $this->dispatcher->addEventListener('result.after', function ($payload) use (&$expectedResult) {
            $expectedResult = $payload['result'];
        });

        $testClass = new class {
            #[EventAfter('result.after')]
            public function testMethod(): array
            {
                return ['key' => 'value'];
            }
        };

        $wrappedMethod = $this->handler->wrapMethod($testClass, 'testMethod');
        $result = $wrappedMethod();

        $this->assertSame($result, $expectedResult, 'After event did not receive the correct method result');
    }

    public function testExceptionDoesNotPreventAfterEvent(): void
    {
        $afterEventTriggered = false;
        
        $this->dispatcher->addEventListener('exception.after', function () use (&$afterEventTriggered) {
            $afterEventTriggered = true;
        });

        $testClass = new class {
            #[EventAfter('exception.after')]
            public function testMethod(): void
            {
                throw new \RuntimeException('Test exception');
            }
        };

        $wrappedMethod = $this->handler->wrapMethod($testClass, 'testMethod');
        
        try {
            $wrappedMethod();
        } catch (\RuntimeException $e) {
            // Expected exception
        }

        $this->assertTrue($afterEventTriggered, 'After event was not triggered when method threw exception');
    }

    public function testMultipleEventListeners(): void
    {
        $count = 0;
        
        // Register multiple listeners for the same event
        for ($i = 0; $i < 3; $i++) {
            $this->dispatcher->addEventListener('multi.after', function () use (&$count) {
                $count++;
            });
        }

        $testClass = new class {
            #[EventAfter('multi.after')]
            public function testMethod(): void
            {
                // Method body
            }
        };

        $wrappedMethod = $this->handler->wrapMethod($testClass, 'testMethod');
        $wrappedMethod();

        $this->assertEquals(3, $count, 'Not all after event listeners were triggered');
    }
}
