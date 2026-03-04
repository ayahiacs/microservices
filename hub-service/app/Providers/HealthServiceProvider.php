<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Spatie\Health\Checks\Checks\DatabaseCheck;
use Spatie\Health\Checks\Checks\DebugModeCheck;
use Spatie\Health\Checks\Checks\EnvironmentCheck;
use Spatie\Health\Checks\Checks\OptimizedAppCheck;
use Spatie\Health\Checks\Checks\RedisCheck;
use Spatie\Health\Checks\Checks\UsedDiskSpaceCheck;
use Spatie\Health\Facades\Health;

class HealthServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        Health::checks([
            DatabaseCheck::new(),
            RedisCheck::new(),
            EnvironmentCheck::new(),
            DebugModeCheck::new(),
            OptimizedAppCheck::new(),
            UsedDiskSpaceCheck::new()->filesystemName('/')->warnWhenUsedSpaceIsAbovePercentage(80)->failWhenUsedSpaceIsAbovePercentage(90),
        ]);
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
