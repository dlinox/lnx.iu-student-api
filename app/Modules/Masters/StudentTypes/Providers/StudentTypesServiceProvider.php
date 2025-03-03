<?php

namespace App\Modules\Masters\StudentTypes\Providers;

use Illuminate\Support\ServiceProvider;

class StudentTypesServiceProvider extends ServiceProvider
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
