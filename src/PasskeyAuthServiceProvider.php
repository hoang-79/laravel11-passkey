<?php

namespace Hoang\PasskeyAuth;

use Illuminate\Support\ServiceProvider;

class PasskeyAuthServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'passkeyauth');
        $this->publishes([
            __DIR__.'/../config/passkey.php' => config_path('passkey.php'),
        ]);
    }

    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/../config/passkey.php', 'passkey');
    }
}
