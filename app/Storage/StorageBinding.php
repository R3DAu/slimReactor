<?php
namespace App\Storage;

readonly class StorageBinding
{
    public function __construct(
        public string $driver,          // e.g. 'database', 'halo', 'xero'
        public string $tableOrSource,   // e.g. 'users', 'Contacts'
        public ?string $idField = 'id', // the primary identifier key
        public array $mapping = []      // map logical fields to physical fields
    ) {}
}
