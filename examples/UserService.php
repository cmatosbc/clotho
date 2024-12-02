<?php

declare(strict_types=1);

namespace Clotho\Examples;

use Clotho\Attribute\EventBefore;
use Clotho\Attribute\EventAfter;

class UserService
{
    private array $users = [];

    #[EventBefore('user.create')]
    #[EventAfter('user.create')]
    public function createUser(string $username, string $email): array
    {
        $user = [
            'id' => uniqid(),
            'username' => $username,
            'email' => $email,
            'created_at' => new \DateTime()
        ];
        
        $this->users[] = $user;
        return $user;
    }

    #[EventBefore('getUser.before')]
    #[EventAfter('getUser.after')]
    public function getUser(string $id): ?array
    {
        foreach ($this->users as $user) {
            if ($user['id'] === $id) {
                // Add last accessed timestamp to demonstrate result modification in after events
                $user['last_accessed'] = new \DateTime();
                return $user;
            }
        }
        return null;
    }

    #[EventBefore('user.delete')]
    #[EventAfter('user.delete')]
    public function deleteUser(string $id): bool
    {
        foreach ($this->users as $key => $user) {
            if ($user['id'] === $id) {
                // Store user before deletion to demonstrate data access in after events
                $deletedUser = $user;
                unset($this->users[$key]);
                return true;
            }
        }
        return false;
    }
}
