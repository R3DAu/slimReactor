<?php

namespace App\Config;

/**
 * Base application configuration values.
 * These values can be overridden in this order:
 * 1. .env
 * 2. config (this file)
 * 3. database (via SettingsService)
 */
class App extends BaseConfig
{
    public bool $displayErrorDetails;
    public string $appName;
    public string $appEnv;
    public string $timezone;

    public function __construct()
    {
        $this->displayErrorDetails = $_ENV['APP_DEBUG'] ?? true;
        $this->appName             = $_ENV['APP_NAME'] ?? 'slimReactor';
        $this->appEnv              = $_ENV['APP_ENV'] ?? 'development';
        $this->timezone            = $_ENV['APP_TIMEZONE'] ?? 'Australia/Melbourne';

        date_default_timezone_set($this->timezone);
    }
}