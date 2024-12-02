# Clotho - PHP Event Attribute Library

[![PHP Version](https://img.shields.io/badge/php-%3E%3D8.1-8892BF.svg)](https://php.net/)
[![License](https://img.shields.io/badge/License-GPL--3.0-blue.svg)](https://www.gnu.org/licenses/gpl-3.0.html)

## Why Clotho?

In Greek mythology, Clotho (meaning "spinner") was one of the three Fates, or Moirai. She was responsible for spinning the thread of human life, determining when pivotal events would occur. Just as Clotho spun the threads that would become significant moments in one's life, this library helps you weave together the events that shape your application's behavior.

## Overview

Clotho is a modern, attribute-based event system for PHP 8.1+. It provides a powerful and intuitive way to handle events in your application using PHP attributes. The library is PSR-14 compliant and offers features like:

- Declarative event handling using PHP attributes
- Support for both PSR-14 event objects and named events
- Priority-based event execution
- Wildcard event patterns
- Event groups
- Comprehensive error handling

## Installation

```bash
composer require cmatosbc/clotho
```

## Basic Usage

For more detailed examples and use cases, check out the `examples/` folder in this repository.

### 1. Simple Event Handling

```php
use Clotho\Attribute\EventBefore;
use Clotho\Attribute\EventAfter;

class UserService
{
    #[EventBefore('user.create')]
    #[EventAfter('user.create')]
    public function createUser(string $name, string $email): array
    {
        return [
            'id' => 1,
            'name' => $name,
            'email' => $email,
        ];
    }
}

// Set up the event system
$dispatcher = new EventDispatcher();
$handler = new EventAttributeHandler($dispatcher);

// Add event listeners
$dispatcher->addEventListener('user.create', function (array $payload) {
    echo "User creation started with email: " . $payload['arguments'][1];
});

// Wrap and use the method
$service = new UserService();
$createUser = $handler->wrapMethod($service, 'createUser');
$result = $createUser('John Doe', 'john@example.com');
```

### 2. Priority-based Execution

```php
use Clotho\Event\BeforeMethodEvent;
use Clotho\Event\AfterMethodEvent;

class OrderService
{
    #[EventBefore]
    public function submitOrder(array $items): array
    {
        return ['order_id' => 123, 'items' => $items];
    }
}

// Higher priority listeners execute first
$dispatcher->addEventListener(BeforeMethodEvent::class, function (BeforeMethodEvent $event) {
    // Validate stock levels
    if (!$this->checkStock($event->getArguments()[0])) {
        $event->stopPropagation();
    }
}, 20);

$dispatcher->addEventListener(BeforeMethodEvent::class, function (BeforeMethodEvent $event) {
    // Check user credit
}, 10);

$dispatcher->addEventListener(BeforeMethodEvent::class, function (BeforeMethodEvent $event) {
    // Log order attempt
}, 0);

// After events with priority
$dispatcher->addEventListener(AfterMethodEvent::class, function (AfterMethodEvent $event) {
    // High priority post-processing
    $result = $event->getResult();
    // Process order result
}, 20);
```

### 3. Wildcard Event Patterns

```php
// Listen to all user events
$dispatcher->addEventListener('user.*', function ($payload) {
    echo "User operation detected: " . $payload['event'];
});

// Listen to all create operations
$dispatcher->addEventListener('*.create', function ($payload) {
    echo "Create operation detected in module: " . $payload['module'];
});

class UserService
{
    #[EventBefore('user.profile.update')]
    #[EventBefore('user.settings.update')]
    public function updateUserData(string $section, array $data): array
    {
        return ['section' => $section, 'data' => $data];
    }
}
```

### 4. Event Groups

```php
class GroupService
{
    #[EventBefore('admin.group:create')]
    #[EventBefore('user.group:update')]
    public function manageGroup(string $operation, array $data): array
    {
        return ['operation' => $operation, 'data' => $data];
    }
}

// Listen to all group creation events
$dispatcher->addEventListener('*.group:create', function ($payload) {
    echo "Group creation in progress...";
});
```

### 5. Event Propagation Control

```php
use Clotho\Event\BeforeMethodEvent;

class UserService
{
    #[EventBefore]
    public function deleteUser(int $userId): bool
    {
        // Delete user logic
        return true;
    }
}

$dispatcher->addEventListener(BeforeMethodEvent::class, function (BeforeMethodEvent $event) {
    if (!$this->hasPermission('delete_users')) {
        echo "Permission denied";
        $event->stopPropagation();
        return;
    }
}, 20);

$dispatcher->addEventListener(BeforeMethodEvent::class, function (BeforeMethodEvent $event) {
    // This won't execute if the previous listener stops propagation
    echo "Deleting user...";
}, 10);
```

### Event Objects and Payload

Clotho supports both event objects and named events with payloads. When using attributes, both types are dispatched:

```php
use Clotho\Event\BeforeMethodEvent;
use Clotho\Event\AfterMethodEvent;
use Clotho\Attribute\EventBefore;
use Clotho\Attribute\EventAfter;

class UserService
{
    #[EventBefore('user.create')]
    #[EventAfter('user.create')]
    public function createUser(string $name, string $email): array
    {
        return [
            'id' => 1,
            'name' => $name,
            'email' => $email,
        ];
    }
}

// Listen for event objects (useful for priority-based handling)
$dispatcher->addEventListener(BeforeMethodEvent::class, function (BeforeMethodEvent $event) {
    $arguments = $event->getArguments();
    echo "Creating user: " . $arguments[0];
}, 20);

// Listen for named events (useful for domain-specific handling)
$dispatcher->addEventListener('user.create', function (array $payload) {
    $event = $payload['event']; // BeforeMethodEvent or AfterMethodEvent
    $arguments = $payload['arguments'];
    echo "User creation event: " . $arguments[0];
});
```

## Advanced Features

### Event Objects

Clotho provides dedicated event objects for different scenarios:

```php
use Clotho\Event\BeforeMethodEvent;
use Clotho\Event\AfterMethodEvent;
use Clotho\Event\BeforeFunctionEvent;
use Clotho\Event\AfterFunctionEvent;

// Listen for method events
$dispatcher->addEventListener(BeforeMethodEvent::class, function (BeforeMethodEvent $event) {
    $methodName = $event->getMethodName();
    $arguments = $event->getArguments();
    
    if (!$this->isValid($arguments)) {
        $event->stopPropagation();
    }
}, 10);

// Listen for after events with results
$dispatcher->addEventListener(AfterMethodEvent::class, function (AfterMethodEvent $event) {
    if ($event->hasException()) {
        $this->handleError($event->getException());
        return;
    }
    
    $result = $event->getResult();
    $this->processResult($result);
});
```

### Priority-based Event Handling

Events can be handled with different priorities, where higher priority listeners execute first:

```php
use Clotho\Event\BeforeMethodEvent;

// High priority validation
$dispatcher->addEventListener(BeforeMethodEvent::class, function (BeforeMethodEvent $event) {
    // Runs first (priority 20)
    if (!$this->hasPermission()) {
        $event->stopPropagation();
    }
}, 20);

// Normal priority logging
$dispatcher->addEventListener(BeforeMethodEvent::class, function (BeforeMethodEvent $event) {
    // Runs second (priority 10)
    $this->logAccess($event->getMethodName());
}, 10);

// Low priority operations
$dispatcher->addEventListener(BeforeMethodEvent::class, function (BeforeMethodEvent $event) {
    // Runs last (priority 0)
    $this->notify($event->getMethodName());
}, 0);
```

### Event Propagation Control

All event objects implement `StoppableEventInterface`, allowing you to control event propagation:

```php
use Clotho\Event\BeforeMethodEvent;

class UserService
{
    #[EventBefore]
    public function deleteUser(int $userId): void
    {
        // Delete user logic
    }
}

// High priority security check
$dispatcher->addEventListener(BeforeMethodEvent::class, function (BeforeMethodEvent $event) {
    if (!$this->hasPermission('delete_users')) {
        $event->stopPropagation(); // Prevents further listeners from executing
        throw new SecurityException('Permission denied');
    }
}, 20);

// This won't execute if permission check fails
$dispatcher->addEventListener(BeforeMethodEvent::class, function (BeforeMethodEvent $event) {
    $this->logDeletion($event->getArguments()[0]);
}, 10);
```

## Error Handling

Clotho provides a set of custom exceptions for handling error conditions in the event system. These exceptions are used only for truly exceptional cases, not for normal flow control.

### Exception Types

```php
use Clotho\Exception\EventDispatchException;
use Clotho\Exception\EventListenerException;
use Clotho\Exception\EventPropagationException;

// Invalid event name
try {
    $dispatcher->dispatch(''); // Empty event name
} catch (EventDispatchException $e) {
    // Handles dispatch-related errors:
    // - Empty or invalid event names
    // - Errors thrown by event listeners
}

// Invalid listener priority
try {
    // Priority must be between -100 and 100
    $dispatcher->addEventListener('user.create', $listener, 150);
} catch (EventListenerException $e) {
    // Handles listener-related errors:
    // - Invalid priority values
    // - Invalid listener types
}
```

### Normal Flow Control

The following scenarios are handled as normal flow control, not exceptions:

```php
// 1. Events with no listeners - perfectly valid
$dispatcher->dispatch('user.created', ['id' => 123]);

// 2. Stopping event propagation - normal control flow
$dispatcher->addEventListener('user.delete', function (BeforeMethodEvent $event) {
    if (!$this->hasPermission()) {
        $event->stopPropagation(); // Stops further listeners, no exception
        return;
    }
    // Process the event
}, 20);

// 3. Wildcard events with no matches - also valid
$dispatcher->dispatch('custom.event');
```

### Exception Hierarchy

```
EventException
├── EventDispatchException  // For dispatch-related errors
├── EventListenerException  // For listener configuration errors
└── EventPropagationException  // Reserved for future propagation-related errors
```

## Event Payload Structure

When using named events, Clotho dispatches an array with the following structure:

#### Before Method Events
```php
[
    'event' => BeforeMethodEvent,    // The actual event object
    'object' => object,              // The object instance the method belongs to
    'method' => string,              // Name of the method being called
    'arguments' => array,            // Array of arguments passed to the method
]
```

#### After Method Events
```php
[
    'event' => AfterMethodEvent,     // The actual event object
    'object' => object,              // The object instance the method belongs to
    'method' => string,              // Name of the method being called
    'arguments' => array,            // Array of arguments passed to the method
    'result' => mixed,               // The return value from the method (if successful)
    'exception' => Throwable|null    // Exception object if method threw an exception
]
```

#### Before Function Events
```php
[
    'event' => BeforeFunctionEvent,  // The actual event object
    'function' => string,            // Name of the function being called
    'arguments' => array,            // Array of arguments passed to the function
]
```

#### After Function Events
```php
[
    'event' => AfterFunctionEvent,   // The actual event object
    'function' => string,            // Name of the function being called
    'arguments' => array,            // Array of arguments passed to the function
    'result' => mixed,               // The return value from the function (if successful)
    'exception' => Throwable|null    // Exception object if function threw an exception
]
```

Example usage with payload:

```php
use Clotho\Attribute\EventBefore;
use Clotho\Attribute\EventAfter;

class UserService
{
    #[EventBefore('user.create')]
    #[EventAfter('user.create')]
    public function createUser(string $name, string $email): array
    {
        return [
            'id' => 1,
            'name' => $name,
            'email' => $email,
        ];
    }
}

// Access payload in before event
$dispatcher->addEventListener('user.create', function (array $payload) {
    $event = $payload['event'];           // BeforeMethodEvent instance
    $object = $payload['object'];         // UserService instance
    $method = $payload['method'];         // "createUser"
    $arguments = $payload['arguments'];   // [$name, $email]
    
    echo "Creating user {$arguments[0]} with email {$arguments[1]}";
});

// Access payload in after event
$dispatcher->addEventListener('user.create', function (array $payload) {
    if (isset($payload['exception'])) {
        echo "Error creating user: " . $payload['exception']->getMessage();
        return;
    }
    
    $result = $payload['result'];  // The returned user array
    echo "Created user with ID: {$result['id']}";
});
```

## Best Practices

1. **Event Naming**:
   - Use lowercase, dot-separated names
   - Start with module/context (e.g., `user.`, `order.`)
   - Use verbs for actions (e.g., `create`, `update`)
   - Use colons for groups (e.g., `admin.group:create`)

2. **Priority Levels**:
   - 20+ : Critical operations (validation, security)
   - 10-19: Core business logic
   - 0-9: Logging, notifications, non-critical operations

3. **Event Payload**:
   - Keep payloads serializable
   - Include only necessary data
   - Consider security implications

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request. For major changes, please open an issue first to discuss what you would like to change.

## License

This project is licensed under the GNU General Public License v3.0 - see the [LICENSE](LICENSE) file for details.

## Acknowledgments

- PSR-14 Event Dispatcher Interface
- PHP 8.1+ Attributes
- The PHP Community

---

Built with ❤️ by [Carlos Artur Matos](https://github.com/cmatosbc)
