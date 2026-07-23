<?php

namespace Tests;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Application;

trait CreatesApplication
{
    /**
     * Creates the application.
     */
    public function createApplication(): Application
    {
        $configCachePath = sys_get_temp_dir().'/toko-online-testing-config.php';

        putenv("APP_CONFIG_CACHE={$configCachePath}");
        $_ENV['APP_CONFIG_CACHE'] = $configCachePath;
        $_SERVER['APP_CONFIG_CACHE'] = $configCachePath;

        $app = require __DIR__.'/../bootstrap/app.php';

        $app->make(Kernel::class)->bootstrap();

        return $app;
    }
}
