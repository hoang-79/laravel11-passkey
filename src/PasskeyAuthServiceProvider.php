<?php

namespace Hoang79\PasskeyAuth;

use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;
use Hoang79\PasskeyAuth\Http\Livewire\LoginForm;
use Hoang79\PasskeyAuth\Console\Commands\InstallPasskeyAuth;


class PasskeyAuthServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'passkeyauth');
        $this->publishes([
            __DIR__.'/../config/passkey.php' => config_path('passkey.php'),
        ]);
        // Publish the migrations
        $this->publishes([
            __DIR__ . '/../database/migrations/' => database_path('migrations')
        ], 'migrations');
        Livewire::component('login-form', LoginForm::class);
    }

    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/../config/passkey.php', 'passkey');
        // Register the command
        $this->commands([
            InstallPasskeyAuth::class,
        ]);
    }
}
