<?php

declare(strict_types=1);

namespace Clotho;

use Clotho\Attribute\EventAfter;
use Clotho\Attribute\EventBefore;
use Clotho\Event\AfterFunctionEvent;
use Clotho\Event\AfterMethodEvent;
use Clotho\Event\BeforeFunctionEvent;
use Clotho\Event\BeforeMethodEvent;
use Clotho\Event\EventDispatcher;
use ReflectionFunction;
use ReflectionMethod;

class EventAttributeHandler
{
    public function __construct(
        private EventDispatcher $dispatcher
    ) {}

    public function wrapMethod(object $object, string $methodName): callable
    {
        $reflectionMethod = new ReflectionMethod($object, $methodName);
        $beforeAttributes = $reflectionMethod->getAttributes(EventBefore::class);
        $afterAttributes = $reflectionMethod->getAttributes(EventAfter::class);

        return function (...$arguments) use ($object, $methodName, $beforeAttributes, $afterAttributes) {
            foreach ($beforeAttributes as $attribute) {
                $beforeEvent = $attribute->newInstance();
                $event = new BeforeMethodEvent($object, $methodName, $arguments);
                
                // Dispatch the event object for priority tests
                $this->dispatcher->dispatch($event);
                
                // Dispatch the named event for attribute tests
                $this->dispatcher->dispatch($beforeEvent->getEventName() ?? $methodName . '.before', [
                    'event' => $event,
                    'object' => $object,
                    'method' => $methodName,
                    'arguments' => $arguments,
                ]);
                
                if ($event->isPropagationStopped()) {
                    break;
                }
            }

            try {
                $result = $object->$methodName(...$arguments);
                
                foreach ($afterAttributes as $attribute) {
                    $afterEvent = $attribute->newInstance();
                    $event = new AfterMethodEvent($object, $methodName, $arguments, $result);
                    
                    // Dispatch the event object for priority tests
                    $this->dispatcher->dispatch($event);
                    
                    // Dispatch the named event for attribute tests
                    $this->dispatcher->dispatch($afterEvent->getEventName() ?? $methodName . '.after', [
                        'event' => $event,
                        'object' => $object,
                        'method' => $methodName,
                        'arguments' => $arguments,
                        'result' => $result,
                    ]);
                    
                    if ($event->isPropagationStopped()) {
                        break;
                    }
                }
                
                return $result;
            } catch (\Throwable $e) {
                foreach ($afterAttributes as $attribute) {
                    $afterEvent = $attribute->newInstance();
                    $event = new AfterMethodEvent($object, $methodName, $arguments, null, $e);
                    
                    // Dispatch the event object for priority tests
                    $this->dispatcher->dispatch($event);
                    
                    // Dispatch the named event for attribute tests
                    $this->dispatcher->dispatch($afterEvent->getEventName() ?? $methodName . '.after', [
                        'event' => $event,
                        'object' => $object,
                        'method' => $methodName,
                        'arguments' => $arguments,
                        'exception' => $e,
                    ]);
                    
                    if ($event->isPropagationStopped()) {
                        break;
                    }
                }
                
                throw $e;
            }
        };
    }

    public function wrapFunction(string $functionName): callable
    {
        $reflectionFunction = new ReflectionFunction($functionName);
        $beforeAttributes = $reflectionFunction->getAttributes(EventBefore::class);
        $afterAttributes = $reflectionFunction->getAttributes(EventAfter::class);

        return function (...$arguments) use ($functionName, $beforeAttributes, $afterAttributes) {
            foreach ($beforeAttributes as $attribute) {
                $beforeEvent = $attribute->newInstance();
                $event = new BeforeFunctionEvent($functionName, $arguments);
                
                // Dispatch the event object for priority tests
                $this->dispatcher->dispatch($event);
                
                // Dispatch the named event for attribute tests
                $this->dispatcher->dispatch($beforeEvent->getEventName() ?? $functionName . '.before', [
                    'event' => $event,
                    'function' => $functionName,
                    'arguments' => $arguments,
                ]);
                
                if ($event->isPropagationStopped()) {
                    break;
                }
            }

            try {
                $result = $functionName(...$arguments);
                
                foreach ($afterAttributes as $attribute) {
                    $afterEvent = $attribute->newInstance();
                    $event = new AfterFunctionEvent($functionName, $arguments, $result);
                    
                    // Dispatch the event object for priority tests
                    $this->dispatcher->dispatch($event);
                    
                    // Dispatch the named event for attribute tests
                    $this->dispatcher->dispatch($afterEvent->getEventName() ?? $functionName . '.after', [
                        'event' => $event,
                        'function' => $functionName,
                        'arguments' => $arguments,
                        'result' => $result,
                    ]);
                    
                    if ($event->isPropagationStopped()) {
                        break;
                    }
                }
                
                return $result;
            } catch (\Throwable $e) {
                foreach ($afterAttributes as $attribute) {
                    $afterEvent = $attribute->newInstance();
                    $event = new AfterFunctionEvent($functionName, $arguments, null, $e);
                    
                    // Dispatch the event object for priority tests
                    $this->dispatcher->dispatch($event);
                    
                    // Dispatch the named event for attribute tests
                    $this->dispatcher->dispatch($afterEvent->getEventName() ?? $functionName . '.after', [
                        'event' => $event,
                        'function' => $functionName,
                        'arguments' => $arguments,
                        'exception' => $e,
                    ]);
                    
                    if ($event->isPropagationStopped()) {
                        break;
                    }
                }
                
                throw $e;
            }
        };
    }
}
