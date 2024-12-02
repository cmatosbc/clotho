<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use Clotho\Examples\UserService;
use Clotho\Event\BeforeMethodEvent;
use Clotho\Event\AfterMethodEvent;
use Clotho\Event\EventDispatcher;
use Clotho\EventAttributeHandler;

// Create the event dispatcher
$dispatcher = new EventDispatcher();

// Helper function to handle both event object and named event
function addEventHandler($dispatcher, $eventName, $handler) {
    // Handle event object
    $dispatcher->addEventListener(BeforeMethodEvent::class, function($event) use ($eventName, $handler) {
        $handler(['event' => $event, 'arguments' => $event->getArguments()]);
    });
    $dispatcher->addEventListener(AfterMethodEvent::class, function($event) use ($eventName, $handler) {
        $handler(['event' => $event, 'arguments' => $event->getArguments(), 'result' => $event->getResult()]);
    });
    // Handle named event
    $dispatcher->addEventListener($eventName, $handler);
}

// Create event listeners
addEventHandler($dispatcher, 'user.create', function ($data) {
    if ($data['event'] instanceof BeforeMethodEvent) {
        $arguments = $data['arguments'];
        echo "Before creating user: {$arguments[0]} ({$arguments[1]})\n";
        
        // Validate email
        if (!filter_var($arguments[1], FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException('Invalid email address');
        }
    } elseif ($data['event'] instanceof AfterMethodEvent && !empty($data['result'])) {
        $result = $data['result'];
        echo "\n=== AFTER EVENT: User Creation ===\n";
        echo "User created successfully: {$result['username']} (ID: {$result['id']})\n";
        echo "Created at: {$result['created_at']->format('Y-m-d H:i:s')}\n";
        echo "===============================\n\n";
    }
});

addEventHandler($dispatcher, 'getUser.before', function ($data) {
    if ($data['event'] instanceof BeforeMethodEvent) {
        $arguments = $data['arguments'];
        echo "Looking up user with ID: {$arguments[0]}\n";
    }
});

addEventHandler($dispatcher, 'getUser.after', function ($data) {
    if ($data['event'] instanceof AfterMethodEvent) {
        $result = $data['result'];
        if ($result) {
            echo "\n=== AFTER EVENT: User Lookup ===\n";
            echo "User lookup completed for: {$result['username']}\n";
            if (isset($result['last_accessed'])) {
                echo "Last accessed: {$result['last_accessed']->format('Y-m-d H:i:s')}\n";
            }
            echo "==============================\n\n";
        } else {
            echo "\n=== AFTER EVENT: User Lookup ===\n";
            echo "User not found\n";
            echo "==============================\n\n";
        }
    }
});

addEventHandler($dispatcher, 'user.delete', function ($data) {
    if ($data['event'] instanceof BeforeMethodEvent) {
        $arguments = $data['arguments'];
        echo "About to delete user with ID: {$arguments[0]}\n";
    } elseif ($data['event'] instanceof AfterMethodEvent) {
        $result = $data['result'];
        $arguments = $data['arguments'];
        echo "\n=== AFTER EVENT: User Deletion ===\n";
        echo "User deletion " . ($result ? "completed" : "failed") . " for ID: {$arguments[0]}\n";
        echo "==============================\n\n";
    }
});

// Create the service with event handling
$userService = new UserService();
$handler = new EventAttributeHandler($dispatcher);

// Wrap the methods to enable event handling
$createUser = $handler->wrapMethod($userService, 'createUser');
$getUser = $handler->wrapMethod($userService, 'getUser');
$deleteUser = $handler->wrapMethod($userService, 'deleteUser');

try {
    // Test the service
    echo "Creating users...\n";
    $user1 = $createUser('john_doe', 'john@example.com');
    $user2 = $createUser('jane_doe', 'jane@example.com');
    
    echo "\nTrying to create user with invalid email...\n";
    try {
        $createUser('invalid_user', 'invalid-email');
    } catch (\InvalidArgumentException $e) {
        echo "Error: " . $e->getMessage() . "\n";
    }
    
    echo "\nRetrieving user...\n";
    // Get the same user twice to see the last_accessed timestamp change
    $foundUser = $getUser($user1['id']);
    echo "First access complete. Waiting 2 seconds...\n";
    sleep(2);
    $foundUser = $getUser($user1['id']);
    
    echo "\nTrying to get non-existent user...\n";
    $notFound = $getUser('non-existent-id');
    
    echo "\nDeleting users...\n";
    // Delete one existing and one non-existent user to demonstrate both scenarios
    $deleteUser($user2['id']);
    $deleteUser('non-existent-id');
    
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
