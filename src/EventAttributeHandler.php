<?php

declare(strict_types=1);

namespace Clotho;

use Clotho\Attribute\EventAfter;
use Clotho\Attribute\EventBefore;
use Clotho\Event\AfterMethodEvent;
use Clotho\Event\BeforeMethodEvent;
use Clotho\Event\EventDispatcher;

class EventAttributeHandler
{
    public function __construct(
        private EventDispatcher $dispatcher
    ) {
    }

    public function wrapMethod(object $object, string $methodName): callable
    {
        $reflectionMethod = new \ReflectionMethod($object, $methodName);
        $beforeAttributes = $reflectionMethod->getAttributes(EventBefore::class);
        $afterAttributes = $reflectionMethod->getAttributes(EventAfter::class);

        return function (...$arguments) use ($object, $methodName, $beforeAttributes, $afterAttributes) {
            foreach ($beforeAttributes as $attribute) {
                $beforeEvent = $attribute->newInstance();
                $event = new BeforeMethodEvent($object, $methodName, $arguments);
                $this->dispatcher->dispatch($event);
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
                    $this->dispatcher->dispatch($event);
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
                    $this->dispatcher->dispatch($event);
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

    public function handleMethodCall(object $object, string $method, array $arguments): mixed
    {
        $beforeEvent = new BeforeMethodEvent($object, $method, $arguments);
        $this->dispatcher->dispatch($beforeEvent);

        if ($beforeEvent->isPropagationStopped()) {
            return null;
        }

        $result = null;
        $exception = null;

        try {
            $result = $object->$method(...$arguments);
        } catch (\Throwable $e) {
            $exception = $e;
        }

        $afterEvent = new AfterMethodEvent($object, $method, $arguments, $result, $exception);
        $this->dispatcher->dispatch($afterEvent);

        if ($exception) {
            throw $exception;
        }

        return $result;
    }

    public function registerAttributeListeners(object $object): void
    {
        $reflection = new \ReflectionObject($object);

        foreach ($reflection->getMethods() as $method) {
            $this->registerMethodListeners($object, $method);
        }
    }

    private function registerMethodListeners(object $object, \ReflectionMethod $method): void
    {
        $beforeAttributes = $method->getAttributes(EventBefore::class);
        $afterAttributes = $method->getAttributes(EventAfter::class);

        foreach ($beforeAttributes as $attribute) {
            $eventBefore = $attribute->newInstance();
            $eventName = $eventBefore->getEventName() ?? BeforeMethodEvent::class;
            $priority = $eventBefore->getPriority();

            $this->dispatcher->addEventListener($eventName, function($event) use ($method, $object) {
                if ($event instanceof BeforeMethodEvent) {
                    return $method->invoke($object, $event);
                }
                return $method->invoke($object, $event);
            }, $priority);
        }

        foreach ($afterAttributes as $attribute) {
            $eventAfter = $attribute->newInstance();
            $eventName = $eventAfter->getEventName() ?? AfterMethodEvent::class;
            $priority = $eventAfter->getPriority();

            $this->dispatcher->addEventListener($eventName, function($event) use ($method, $object) {
                if ($event instanceof AfterMethodEvent) {
                    return $method->invoke($object, $event);
                }
                return $method->invoke($object, $event);
            }, $priority);
        }
    }
}
