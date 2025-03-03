<?php

namespace App\Modules\PaymentMethod\Providers;

use Illuminate\Support\ServiceProvider;

class PaymentMethodServiceProvider extends ServiceProvider
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
