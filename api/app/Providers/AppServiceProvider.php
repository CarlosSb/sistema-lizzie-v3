<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->bind(
            \Illuminate\Contracts\Console\Kernel::class,
            \App\Console\Kernel::class
        );

        $this->app->bind(
            \Illuminate\Contracts\Http\Kernel::class,
            \App\Http\Kernel::class
        );
    }

    public function boot()
    {
    }
}