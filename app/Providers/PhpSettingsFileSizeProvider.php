<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class PhpSettingsFileSizeProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        // Set PHP configuration values at runtime
        ini_set('upload_max_filesize', '100M');
        ini_set('post_max_size', '100M');
        ini_set('max_input_vars', '5000');
    }
}
