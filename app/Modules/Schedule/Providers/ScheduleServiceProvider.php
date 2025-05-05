<?php

namespace App\Modules\Schedule\Providers;

use Illuminate\Support\ServiceProvider;

class ScheduleServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->loadRoutesFrom(__DIR__ . '/../Routes/api.php');
    }

    public function register()
    {
        
    }
}
