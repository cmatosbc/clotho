<?php

declare(strict_types=1);

namespace Clotho\Event;

use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\EventDispatcher\ListenerProviderInterface;
use Psr\EventDispatcher\StoppableEventInterface;

class EventDispatcher implements EventDispatcherInterface, ListenerProviderInterface
{
    private array $listeners = [];
    private array $wildcardListeners = [];

    public function dispatch(object|string $event, array $payload = []): object|array
    {
        if (is_string($event)) {
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
        foreach ($listeners as $listener) {
            $result = $listener($event);
            
            if ($result === false || ($event instanceof StoppableEventInterface && $event->isPropagationStopped())) {
                break;
            }
        }

        return $event;
    }

    public function addEventListener(string $eventName, callable $listener, int $priority = 0): void
    {
        if (strpos($eventName, '*') !== false) {
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

    public function getListenersForEvent(object|string $event): iterable
    {
        $eventName = is_string($event) ? $event : get_class($event);
        $matchingListeners = [];

        // Add exact match listeners
        if (isset($this->listeners[$eventName])) {
            foreach ($this->listeners[$eventName] as $listenerData) {
                $matchingListeners[] = $listenerData;
            }
        }

        // Add wildcard listeners
        foreach ($this->wildcardListeners as $pattern => $listeners) {
            if ($this->matchesWildcard($eventName, $pattern)) {
                foreach ($listeners as $listenerData) {
                    $matchingListeners[] = $listenerData;
                }
            }
        }

        // Sort all listeners by priority
        usort($matchingListeners, fn($a, $b) => $b['priority'] <=> $a['priority']);

        return array_map(fn($listenerData) => $listenerData['listener'], $matchingListeners);
    }

    private function matchesWildcard(string $eventName, string $pattern): bool
    {
        $pattern = str_replace('\\*', '.*', preg_quote($pattern, '/'));
        return (bool) preg_match('/^' . $pattern . '$/', $eventName);
    }

    public function getListeners(): array
    {
        return array_merge($this->listeners, $this->wildcardListeners);
    }
}
