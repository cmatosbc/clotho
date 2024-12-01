<?php

declare(strict_types=1);

namespace Clotho\Event;

use Clotho\Exception\EventDispatchException;
use Clotho\Exception\EventListenerException;
use Clotho\Exception\EventPropagationException;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\EventDispatcher\ListenerProviderInterface;
use Psr\EventDispatcher\StoppableEventInterface;

class EventDispatcher implements EventDispatcherInterface, ListenerProviderInterface
{
    private array $listeners = [];
    private array $wildcardListeners = [];

    public function addEventListener(string $eventName, callable $listener, int $priority = 0): void
    {
        if ($priority < -100 || $priority > 100) {
            throw EventListenerException::invalidPriority($priority);
        }

        if (strpos($eventName, '*') !== false || strpos($eventName, '{') !== false) {
            $this->wildcardListeners[$eventName][] = [
                'priority' => $priority,
                'listener' => $listener,
            ];
            usort($this->wildcardListeners[$eventName], fn($a, $b) => $b['priority'] <=> $a['priority']);
        } else {
            $this->listeners[$eventName][] = [
                'priority' => $priority,
                'listener' => $listener,
            ];
            usort($this->listeners[$eventName], fn($a, $b) => $b['priority'] <=> $a['priority']);
        }
    }

    public function dispatch(object|string $event, array $payload = []): object|array
    {
        if (is_string($event)) {
            if (empty($event)) {
                throw EventDispatchException::invalidEventName($event);
            }
            $eventName = $event;
            $event = $payload;
        } else {
            $eventName = get_class($event);
            $event = $event;
        }

        if ($event instanceof StoppableEventInterface && $event->isPropagationStopped()) {
            return $event;
        }

        $listeners = $this->getListenersForEvent($eventName);
        
        if (empty($listeners) && !str_contains($eventName, '*')) {
            // Removed the exception throw here
        }

        try {
            foreach ($listeners as $listener) {
                $result = $listener($event);
                
                if ($result === false || ($event instanceof StoppableEventInterface && $event->isPropagationStopped())) {
                    break;
                }
            }
        } catch (\Throwable $e) {
            if (!$e instanceof EventException) {
                throw EventDispatchException::dispatchError($eventName, $e->getMessage());
            }
            throw $e;
        }

        return $event;
    }

    public function getListenersForEvent(object|string $event): iterable
    {
        $eventName = is_string($event) ? $event : get_class($event);
        $matchingListeners = [];

        if (isset($this->listeners[$eventName])) {
            foreach ($this->listeners[$eventName] as $listenerData) {
                $matchingListeners[] = $listenerData;
            }
        }

        foreach ($this->wildcardListeners as $pattern => $listeners) {
            if ($this->matchesWildcard($eventName, $pattern)) {
                foreach ($listeners as $listenerData) {
                    $matchingListeners[] = $listenerData;
                }
            }
        }

        usort($matchingListeners, fn($a, $b) => $b['priority'] <=> $a['priority']);

        return array_map(fn($listenerData) => $listenerData['listener'], $matchingListeners);
    }

    public function getListeners(): array
    {
        return array_merge($this->listeners, $this->wildcardListeners);
    }

    private function matchesWildcard(string $eventName, string $pattern): bool
    {
        if (strpos($pattern, '{') !== false) {
            $pattern = preg_replace_callback('/\{([^}]+)\}/', function($matches) {
                return '(' . str_replace(',', '|', $matches[1]) . ')';
            }, $pattern);
        }

        $pattern = str_replace(['*', '.'], ['[^.]+', '\\.'], $pattern);
        
        return (bool)preg_match('/^' . $pattern . '$/', $eventName);
    }
}
