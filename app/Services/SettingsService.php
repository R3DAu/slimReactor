<?php
namespace App\Services;

use App\Providers\ConfigRepositoryProvider;
use App\Storage\StorageManager;
use App\Types\SettingTypeDefinition;
use App\Services\EncryptionService;

class SettingsService
{
    protected array $cache = [];

    public function __construct(
        protected StorageManager $storage,
        protected EncryptionService $encryption,
        protected ConfigRepositoryProvider $configRepositoryProvider
    ) {}

    public function get(string $key, string $scope = 'global'): ?string
    {
        if (isset($this->cache[$key])) return $this->cache[$key];

        if (isset($_ENV[$key])) return $_ENV[$key];

        // DB lookup
        $record = $this->storage->fetchAll(new SettingTypeDefinition(), ['key_name' => $key]);
        $setting = reset($record);
        if ($setting) {
            $value = $setting->get('value');
            return $this->cache[$key] = $value;
        }

        // config repository lookup
        $default = $this->configRepositoryProvider->get($key) ?? null;
        return $this->cache[$key] = $default;
    }

    public function set(string $key, string $value, string $scope = 'global', bool $encrypt = false): void
    {
        $type = new SettingTypeDefinition();

        // Upsert logic depending on your StorageManager
        $this->storage->save(new \App\Types\Model($type, [
            'scope' => $scope,
            'key_name' => $key,
            'value' => $value,
            'encrypted' => $encrypt
        ]));
    }
}
