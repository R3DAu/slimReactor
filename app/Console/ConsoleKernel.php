<?php

namespace App\Console;

use App\Types\TypeRegistry;
use App\Storage\StorageManager;
use App\Types\Model;

class ConsoleKernel
{
    public function __construct(
        protected TypeRegistry $registry,
        protected StorageManager $storage
    ) {}

    public function handle(array $argv): void
    {
        $command = $argv[1] ?? null;

        if (!$command) {
            echo "\nUsage: php cli.php <command> [options]\n";
            exit(1);
        }

        match ($command) {
            'make:user' => $this->createUser($argv),
            'make:role' => $this->createRole($argv),
            'make:hmac-key' => $this->createHmacKey($argv),
            default => $this->unknownCommand($command)
        };
    }

    protected function createHmacKey(array $argv): void
    {
        $name = $argv[2] ?? null;
        $userId = $argv[3] ?? null;

        if (!$name || !$userId) {
            echo "\nUsage: php cli.php make:hmac-key <name> <user_id>\n";
            return;
        }

        $type = $this->registry->get('api_client');
        $model = new Model($type, [
            'name' => $name,
            'api_key' => bin2hex(random_bytes(16)),
            'api_secret' => bin2hex(random_bytes(32)),
            'user_id' => (int)$userId,
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        $this->storage->save($model);
        echo "\n✅ HMAC key for '{$name}' created.\n";
    }

    protected function createUser(array $argv): void
    {
        $email = $argv[2] ?? null;
        $password = $argv[3] ?? null;
        $name = $argv[4] ?? 'Unnamed';
        $roles = isset($argv[5]) ? explode(',', $argv[5]) : [];

        if (!$email) {
            echo "\nUsage: php cli.php make:user <email> <password> [name] [roles] \n";
            return;
        }

        $type = $this->registry->get('user');
        $model = new Model($type, [
            'email' => $email,
            'password' => password_hash($password, PASSWORD_DEFAULT),
            'name' => $name,
            'roles' => $roles,
            'is_active' => true,
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        if ($this->storage->exists($type, $email, 'email_address')) {
            echo "\n❌ User with email '{$email}' already exists.\n";
            return;
        }

        $this->storage->save($model);
        echo "\n✅ User '{$email}' created.\n";
    }

    protected function createRole(array $argv): void
    {
        $name = $argv[2] ?? null;
        $permissions = isset($argv[3]) ? explode(',', $argv[3]) : [];

        if (!$name) {
            echo "\nUsage: php cli.php make:role <name> [permissions]\n";
            return;
        }

        $type = $this->registry->get('role');
        $model = new Model($type, [
            'name' => $name,
            'permissions' => $permissions,
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        $this->storage->save($model);
        echo "\n✅ Role '{$name}' created.\n";
    }

    protected function unknownCommand(string $command): void
    {
        echo "\n❌ Unknown command: {$command}\n";
    }
}