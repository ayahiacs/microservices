<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class BroadcastServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // Register channel authorization routes
        if (file_exists(base_path('routes/channels.php'))) {
            $this->loadRoutesFrom(base_path('routes/channels.php'));
        }
    }
}
