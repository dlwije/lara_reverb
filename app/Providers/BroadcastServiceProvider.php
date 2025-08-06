<?php

namespace App\Providers;

use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\ServiceProvider;

class BroadcastServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Web (Inertia/Sanctum)
        Broadcast::routes([
            'middleware' => ['web', 'auth:sanctum'],
        ]);

        // API (mobile/passport)
        Broadcast::routes([
            'prefix' => 'api',
            'middleware' => ['auth:api'],
            'as' => 'api.',
        ]);

        require base_path('routes/channels.php');
    }
}
