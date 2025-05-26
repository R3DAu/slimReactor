<?php

/* Paths Configuration
   This file defines the folder and system paths used by the application. (URL paths are defined in the App.php file)
*/

namespace App\Config;

class Paths extends BaseConfig
{
    public readonly string $rootDirectory;
    public readonly string $appDirectory;
    public readonly string $configDirectory;
    public readonly string $publicDirectory;
    public readonly string $storageDirectory;
    public readonly string $vendorDirectory;
    public readonly string $logsDirectory;
    public readonly string $cacheDirectory;
    public readonly string $writeableDirectory;
    public readonly string $routesDirectory;


    public function __construct()
    {
        $this->rootDirectory        = realpath(__DIR__ . '/../..');
        $this->appDirectory         = realpath(__DIR__ . '/../');
        $this->configDirectory      = realpath(__DIR__);

        $publicDirectory      = $this->rootDirectory . '/public';
        $vendorDirectory      = $this->rootDirectory . '/vendor';
        $storageDirectory     = $this->appDirectory . '/Storage';
        $routesDirectory      = $this->appDirectory . '/Routes';
        $logsDirectory        = $storageDirectory . '/Logs';
        $cacheDirectory       = $storageDirectory . '/Cache';
        $writeableDirectory   = $storageDirectory . '/Writable';

        /* define the root directory */
        if (!defined('ROOTPATH')) {
            define('ROOTPATH', $this->rootDirectory);
        }
        /* define the app directory */
        if (!defined('APPPATH')) {
            define('APPPATH', $this->appDirectory);
        }
        /* define the config directory */
        if (!defined('CONFIGPATH')) {
            define('CONFIGPATH', $this->configDirectory);
        }
        /* define the public directory */
        if (!defined('PUBLICPATH')) {
            define('PUBLICPATH', $publicDirectory);
        }
        /* define the storage directory */
        if (!defined('STORAGEPATH')) {
            define('STORAGEPATH', $storageDirectory);
        }
        /* define the writeable directory */
        if (!defined('WRITEABLEPATH')) {
            define('WRITEABLEPATH', $storageDirectory . '/Writeable');
        }
        /* define the cache directory */
        if (!defined('CACHEPATH')) {
            define('CACHEPATH', $storageDirectory . '/Cache');
        }
        /* define the logs directory */
        if (!defined('LOGPATH')) {
            define('LOGPATH', $storageDirectory . '/Logs');
        }
        /* define the vendor directory */
        if (!defined('VENDORPATH')) {
            define('VENDORPATH', $vendorDirectory);
        }
        /* define the routes directory */
        if (!defined('ROUTESPATH')) {
            define('ROUTESPATH', $routesDirectory);
        }

        // Ensure all directories exist
        $this->ensureDirectoryExists($publicDirectory);
        $this->ensureDirectoryExists($storageDirectory);
        $this->ensureDirectoryExists($vendorDirectory);
        $this->ensureDirectoryExists($logsDirectory);
        $this->ensureDirectoryExists($cacheDirectory);
        $this->ensureDirectoryExists($writeableDirectory);
        $this->ensureDirectoryExists($routesDirectory);

        $this->publicDirectory      = realpath($publicDirectory);
        $this->vendorDirectory      = realpath($vendorDirectory);
        $this->storageDirectory     = realpath($storageDirectory);
        $this->routesDirectory      = realpath($routesDirectory);
        $this->logsDirectory        = realpath($logsDirectory);
        $this->cacheDirectory       = realpath($cacheDirectory);
        $this->writeableDirectory   = realpath($writeableDirectory);
    }

    private function ensureDirectoryExists(string $path): void
    {
        if (!is_dir($path)) {
            mkdir($path, 0755, true);
        }
    }
}
