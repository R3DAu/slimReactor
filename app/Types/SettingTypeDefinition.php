<?php
namespace App\Types;

use App\Services\EncryptionService;
use App\Storage\StorageBinding;

class SettingTypeDefinition extends TypeDefinition
{
    protected EncryptionService $crypto;

    protected array $encryptedFields = [
        'value', // or specific sensitive keys only
    ];

    public function __construct()
    {
        parent::__construct(
            'setting',
            [
                'id'         => FieldType::INTEGER,
                'scope'      => FieldType::STRING,
                'key_name'   => FieldType::STRING,
                'value'      => FieldType::STRING,
                'encrypted'  => FieldType::BOOLEAN,
                'created_at' => FieldType::DATETIME,
                'updated_at' => FieldType::DATETIME,
            ],
            new StorageBinding(
                driver: 'database',
                tableOrSource: 'settings',
                idField: 'id',
                mapping: [
                    'scope'      => 'scope',
                    'key_name'   => 'key_name',
                    'value'      => 'value',
                    'encrypted'  => 'encrypted',
                ]
            )
        );
    }
}
