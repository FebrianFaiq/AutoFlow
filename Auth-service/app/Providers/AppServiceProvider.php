<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\DatabasePresenceVerifier;

class AppServiceProvider extends ServiceProvider
{
    public function register()
    {
        //
    }

    public function boot()
    {
        if (app()->bound('db')) {
            $verifier = new DatabasePresenceVerifier(app('db'));
            app('validator')->setPresenceVerifier($verifier);
        }
    }
}