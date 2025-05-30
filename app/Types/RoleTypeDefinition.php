<?php

namespace App\Types;

use App\Storage\StorageBinding;

class RoleTypeDefinition extends TypeDefinition
{
    public function __construct()
    {
        parent::__construct(
            'role',
            [
                'id' => FieldType::INTEGER,
                'name' => FieldType::STRING,
                'permissions' => FieldType::ARRAY,
                'created_at' => FieldType::DATETIME,
            ],
            new StorageBinding(
                driver: 'database',
                tableOrSource: 'roles',
                idField: 'id',
                mapping: [
                    'name' => 'name',
                    'permissions' => 'permission_keys',
                    'created_at' => 'created_at',
                ]
            )
        );
    }
}
