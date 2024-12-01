<?php

declare(strict_types=1);

namespace Clotho\Tests\Unit;

use Clotho\Attribute\EventAfter;
use Clotho\Attribute\EventBefore;
use Clotho\Event\EventDispatcher;
use Clotho\EventAttributeHandler;
use PHPUnit\Framework\TestCase;

class EventWildcardTest extends TestCase
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

    public function testWildcardEventListener(): void
    {
        $testClass = new class {
            #[EventBefore('user.create')]
            #[EventBefore('user.update')]
            #[EventBefore('user.delete')]
            public function userOperation(): void
            {
            }
        };

        // Add a wildcard listener for all user operations
        $this->dispatcher->addEventListener('user.*', function ($payload) {
            $this->eventLog[] = 'wildcard';
        });

        $wrappedMethod = $this->handler->wrapMethod($testClass, 'userOperation');
        $wrappedMethod();

        $this->assertCount(3, $this->eventLog, 'Wildcard listener should be triggered for all user events');
    }

    public function testEventGroupListener(): void
    {
        $testClass = new class {
            #[EventBefore('user.group:create')]
            #[EventBefore('user.group:update')]
            #[EventBefore('admin.group:create')]
            public function groupOperation(): void
            {
            }
        };

        // Add a listener for all create operations in any group
        $this->dispatcher->addEventListener('*.group:create', function ($payload) {
            $this->eventLog[] = 'create';
        });

        $wrappedMethod = $this->handler->wrapMethod($testClass, 'groupOperation');
        $wrappedMethod();

        $this->assertCount(2, $this->eventLog, 'Group listener should be triggered for all create events');
    }

    public function testMultipleWildcardPatterns(): void
    {
        $testClass = new class {
            #[EventBefore('user.profile.update')]
            #[EventBefore('user.settings.update')]
            #[EventBefore('admin.profile.update')]
            public function updateOperation(): void
            {
            }
        };

        // Add listeners with different wildcard patterns
        $this->dispatcher->addEventListener('user.*.*', function ($payload) {
            $this->eventLog[] = 'user_wildcard';
        });

        $this->dispatcher->addEventListener('*.profile.*', function ($payload) {
            $this->eventLog[] = 'profile_wildcard';
        });

        $wrappedMethod = $this->handler->wrapMethod($testClass, 'updateOperation');
        $wrappedMethod();

        $this->assertContains('user_wildcard', $this->eventLog, 'User wildcard should match user events');
        $this->assertContains('profile_wildcard', $this->eventLog, 'Profile wildcard should match profile events');
        $this->assertCount(4, $this->eventLog, 'Multiple wildcards should match appropriately');
    }

    public function testWildcardPriority(): void
    {
        $testClass = new class {
            #[EventBefore('user.create')]
            public function createUser(): void
            {
            }
        };

        // Add listeners with different priorities
        $this->dispatcher->addEventListener('user.*', function ($payload) {
            $this->eventLog[] = 'wildcard_high';
        }, 10);

        $this->dispatcher->addEventListener('user.create', function ($payload) {
            $this->eventLog[] = 'exact_medium';
        }, 5);

        $this->dispatcher->addEventListener('*.*', function ($payload) {
            $this->eventLog[] = 'wildcard_low';
        }, 0);

        $wrappedMethod = $this->handler->wrapMethod($testClass, 'createUser');
        $wrappedMethod();

        $this->assertEquals(
            ['wildcard_high', 'exact_medium', 'wildcard_low'],
            $this->eventLog,
            'Listeners should execute in priority order regardless of wildcard pattern'
        );
    }

    public function testWildcardEventPropagation(): void
    {
        $testClass = new class {
            #[EventBefore('user.create')]
            public function createUser(): void
            {
            }
        };

        // Add listeners with propagation control
        $this->dispatcher->addEventListener('user.*', function ($payload) {
            $this->eventLog[] = 'wildcard_before_stop';
            return false; // Stop propagation
        }, 10);

        $this->dispatcher->addEventListener('user.create', function ($payload) {
            $this->eventLog[] = 'exact_match';
        }, 5);

        $wrappedMethod = $this->handler->wrapMethod($testClass, 'createUser');
        $wrappedMethod();

        $this->assertEquals(
            ['wildcard_before_stop'],
            $this->eventLog,
            'Event propagation should stop after wildcard listener returns false'
        );
    }
}
