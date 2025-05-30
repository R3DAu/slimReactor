<?php
namespace App\Types;

use App\Storage\StorageBinding;

readonly class ApiClientTypeDefinition extends TypeDefinition
{
    public function __construct()
    {
        parent::__construct(
            'api_client',
            [
                'id'         => FieldType::INTEGER,
                'name'       => FieldType::STRING,
                'api_key'    => FieldType::STRING,
                'api_secret' => FieldType::STRING,
                'user_id'    => FieldType::INTEGER,
                'created_at' => FieldType::DATETIME,
            ],
            new StorageBinding(
                driver: 'database',
                tableOrSource: 'api_clients',
                idField: 'id',
                mapping: [
                    'name'       => 'name',
                    'api_key'    => 'api_key',
                    'api_secret' => 'api_secret',
                    'user_id'    => 'user_id',
                    'created_at' => 'created_at',
                ]
            )
        );
    }
}

