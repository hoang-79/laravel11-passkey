<?php

namespace Hoang79\PasskeyAuth\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;

class InstallPasskeyAuth extends Command
{
    protected $signature = 'passkey:install';
    protected $description = 'Install the PasskeyAuth package';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $this->info('Installing PasskeyAuth package...');

        $this->updateFortifyConfig();
        $this->updateJetstreamConfig();
        $this->updateAuthConfig();
        $this->updateAppConfig();
        $this->updateWelcomeView();
        $this->copyMigrations();

        $this->info('PasskeyAuth package installed successfully.');
    }

    protected function updateFortifyConfig()
    {
        $this->info('Updating fortify.php config...');

        $path = config_path('fortify.php');
        $contents = file_get_contents($path);

        $search = "'features' => [";
        $replace = "'features' => [
        //Features::registration(),
        //Features::resetPasswords(),
        //Features::emailVerification(),
        Features::updateProfileInformation(),
        //Features::updatePasswords(),
        /*Features::twoFactorAuthentication([
            'confirm' => true,
            'confirmPassword' => true,
            // 'window' => 0,
        ]),*/";

        $contents = str_replace($search, $replace, $contents);

        file_put_contents($path, $contents);
    }

    protected function updateJetstreamConfig()
    {
        $this->info('Updating jetstream.php config...');

        $path = config_path('jetstream.php');
        $contents = file_get_contents($path);

        $search = "'features' => [";
        $replace = "'features' => [
        // Features::termsAndPrivacyPolicy(),
        Features::profilePhotos(),
        //Features::api(),
        Features::teams(['invitations' => true]),
        Features::accountDeletion(),";

        $contents = str_replace($search, $replace, $contents);

        file_put_contents($path, $contents);
    }

    protected function updateAuthConfig()
    {
        $this->info('Updating auth.php config...');

        $path = config_path('auth.php');
        $contents = file_get_contents($path);

        $search = "'providers' => [";
        $replace = "'providers' => [
        'users' => [
            'driver' => 'eloquent-webauthn',
            'model' => env('AUTH_MODEL', App\Models\User::class),
            'password_fallback' => true,
        ],
        /*'users' => [
            'driver' => 'eloquent',
            'model' => env('AUTH_MODEL', App\Models\User::class),
        ],*/";

        $contents = str_replace($search, $replace, $contents);

        file_put_contents($path, $contents);
    }

    protected function updateAppConfig()
    {
        $this->info('Updating app.php config...');

        $path = base_path('bootstrap/app.php');

        $contents = file_get_contents($path);

        if (strpos($contents, 'validateCsrfTokens') === false) {
            $search = '->withMiddleware(function (Middleware $middleware) {';
            $replace = '->withMiddleware(function (Middleware $middleware) {
        $middleware->validateCsrfTokens(except: [
            \'passkey\',
            \'custom-register\',
            \'verify-otp\',
            \'webauthn-register\',
            \'webauthn-register-response\',
            \'webauthn-authenticate\',
            \'webauthn-authenticate-response\',
        ]);';
            $contents = str_replace($search, $replace, $contents);
        } else {
            $search = '$middleware->validateCsrfTokens(except: [';
            $replace = '$middleware->validateCsrfTokens(except: [
            \'passkey\',
            \'custom-register\',
            \'verify-otp\',
            \'webauthn-register\',
            \'webauthn-register-response\',
            \'webauthn-authenticate\',
            \'webauthn-authenticate-response\',';

            $contents = str_replace($search, $replace, $contents);
        }

        if (strpos($contents, 'redirectGuestsTo') === false) {
            $search = '->withMiddleware(function (Middleware $middleware) {';
            $replace = '->withMiddleware(function (Middleware $middleware) {
        $middleware->redirectGuestsTo(\'/passkey\');';
            $contents = str_replace($search, $replace, $contents);
        } else {
            $search = '$middleware->redirectGuestsTo(\'/';
            $replace = '$middleware->redirectGuestsTo(\'/passkey\');';
            $contents = str_replace($search, $replace, $contents);
        }

        file_put_contents($path, $contents);
    }

    protected function updateWelcomeView()
    {
        $this->info('Updating welcome.blade.php view...');

        $path = resource_path('views/welcome.blade.php');
        $contents = file_get_contents($path);

        $search = '<a
                                        href="{{ route(\'login\') }}"
                                        class="rounded-md px-3 py-2 text-black ring-1 ring-transparent transition hover:text-black/70 focus:outline-none focus-visible:ring-[#FF2D20] dark:text-white dark:hover:text-white/80 dark:focus-visible:ring-white"
                                    >
                                        Log in
                                    </a>

                                    @if (Route::has(\'register\'))
                                        <a
                                            href="{{ route(\'register\') }}"
                                            class="rounded-md px-3 py-2 text-black ring-1 ring-transparent transition hover:text-black/70 focus:outline-none focus-visible:ring-[#FF2D20] dark:text-white dark:hover:text-white/80 dark:focus-visible:ring-white"
                                        >
                                            Register
                                        </a>
                                    @endif';

        $replace = '<a
                                        href="{{ route(\'passkey\') }}"
                                        class="rounded-md px-3 py-2 text-black ring-1 ring-transparent transition hover:text-black/70 focus:outline-none focus-visible:ring-[#FF2D20] dark:text-white dark:hover:text-white/80 dark:focus-visible:ring-white"
                                    >
                                        Log in
                                    </a>';

        $contents = str_replace($search, $replace, $contents);

        file_put_contents($path, $contents);
    }

    protected function copyMigrations()
    {
        $this->info('Copying migration files...');

        $filesystem = new Filesystem;
        $sourcePath = __DIR__ . '/../../database/migrations/';
        $destinationPath = database_path('migrations/');

        $files = [
            '2024_07_09_100744_create_temporary_email_otps_table.php',
            '2024_07_17_111856_authenticators.php',
        ];

        foreach ($files as $file) {
            $filesystem->copy($sourcePath . $file, $destinationPath . $file);
        }
    }
}
