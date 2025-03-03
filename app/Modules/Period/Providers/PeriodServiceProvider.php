<?php

namespace App\Modules\Period\Providers;

use Illuminate\Support\ServiceProvider;

class PeriodServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->loadRoutesFrom(__DIR__ . '/../Routes/api.php');
    }

    public function register()
    {
        //
    }
}
