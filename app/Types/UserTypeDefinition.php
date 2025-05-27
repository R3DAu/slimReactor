<?php

namespace App\Types;

use App\Storage\StorageBinding;

readonly class UserTypeDefinition extends TypeDefinition
{
    public function __construct()
    {
        parent::__construct(
            'user',
            [
                'id'         => FieldType::INTEGER,
                'email'      => FieldType::STRING,
                'name'       => FieldType::STRING,
                'roles'      => FieldType::ARRAY,
                'is_active'  => FieldType::BOOLEAN,
                'created_at' => FieldType::DATETIME,
            ],
            new StorageBinding(
                driver: 'database',
                tableOrSource: 'users',
                idField: 'id',
                mapping: [
                    'email' => 'email_address',
                    'roles' => 'role_ids',
                ]
            )
        );
    }
}
