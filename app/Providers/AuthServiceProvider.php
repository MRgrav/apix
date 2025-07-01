<?php

namespace App\Providers;

// use Illuminate\Support\Facades\Gate;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        //
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        //
        $this->registerPolicies();

        // Gate::define('viewLogViewer', function ($user = null) {
        //     // Replace 'YOUR_IP_ADDRESS' with your actual public IP (e.g., '203.0.113.42')
        //     \Log::info('LogViewer attempt from IP: ' . request()->ip() . 'cookies: ' . json_encode(request()->cookies->all()));
        //     \Log::info('user: ' . $user . ' phone: ' . $user);
        //     $allowedIps = ['YOUR_PUBLIC_IP', 'YOUR_PUBLIC_IPV6'];
        //     return in_array(request()->ip(), $allowedIps);
        //     // && request()->cookie('logviewer_key') === 'your_secret_value';
        // });
    }
}
