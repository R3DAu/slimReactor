<?php
namespace App\Services;

namespace App\Services;

use App\Types\UserTypeDefinition;
use App\Types\RoleTypeDefinition;
use App\Types\ApiClientTypeDefinition;

class PermissionService
{
    public function __construct(
        protected \App\Storage\StorageManager $storage
    ) {}

    public function hasPermission(array $grantedPermissions, string $required): bool
    {
        foreach ($grantedPermissions as $permission) {
            if ($this->matches($permission, $required)) {
                return true;
            }
        }

        return false;
    }

    protected function matches(string $granted, string $required): bool
    {
        [$gDomain, $gAction] = explode(':', $granted . ':');
        [$rDomain, $rAction] = explode(':', $required . ':');

        return ($gDomain === '*' || $gDomain === $rDomain) &&
            ($gAction === '*' || $gAction === $rAction);
    }

    public function getPermissionsForUser(array $user): array
    {
        $roleNames = $user['roles'] ?? [];
        if (is_string($roleNames)) {
            $roleNames = json_decode($roleNames, true) ?? [];
        }

        $roleType = new RoleTypeDefinition();
        $allRoles = $this->storage->fetchAll($roleType);

        $permissions = [];

        foreach ($allRoles as $roleModel) {
            $roleData = $roleModel->all();
            if (in_array($roleData['name'], $roleNames)) {
                $permissions = array_merge($permissions, json_decode($roleData['permissions'], true) ?? []);
            }
        }

        return array_unique($permissions);
    }

    public function getPermissionsForApiKey(array $apiKey): array
    {
        $permissions = [];

        // 1. Direct permissions on the API key (if any)
        if (!empty($apiKey['permissions'])) {
            $permissions = is_string($apiKey['permissions'])
                ? json_decode($apiKey['permissions'], true)
                : $apiKey['permissions'];
        }

        // 2. Linked user (if user_id exists)
        if (!empty($apiKey['user_id'])) {
            $userType = new UserTypeDefinition();
            $user = $this->storage->fetch($userType, $apiKey['user_id']);

            if ($user) {
                $permissions = array_merge($permissions, $this->getPermissionsForUser($user->all()));
            }
        }

        return array_unique($permissions);
    }
}
