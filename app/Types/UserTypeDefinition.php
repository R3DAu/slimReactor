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
                'password'   => FieldType::STRING,   // ✅ Added
                'roles'      => FieldType::ARRAY,
                'is_active'  => FieldType::BOOLEAN,
                'created_at' => FieldType::DATETIME,
            ],
            new StorageBinding(
                driver: 'database',
                tableOrSource: 'users',
                idField: 'id',
                mapping: [
                    'id'         => 'id',               // ✅ Mapped to DB column
                    'email'      => 'email_address',
                    'password'   => 'password_hash',     // ✅ Mapped to DB column
                    'roles'      => 'role_ids',
                    'is_active'  => 'is_active',
                    'created_at' => 'created_at',
                    'name'       => 'name'
                ]
            )
        );
    }
}
