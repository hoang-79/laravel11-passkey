<?php

namespace Hoang79\PasskeyAuth\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;

/**
 * Class InstallPasskeyAuth
 *
 * This command installs the PasskeyAuth package by updating configuration files and copying necessary migrations.
 *
 * @package Hoang79\PasskeyAuth\Console\Commands
 */
class InstallPasskeyAuth extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'passkey:install';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Install the PasskeyAuth package';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $this->info('Installing PasskeyAuth package...');

        // Update configuration files
        $this->updateFortifyConfig();
        $this->updateJetstreamConfig();
        $this->updateAuthConfig();
        $this->updateAppConfig();
        $this->updateWelcomeView();
        $this->updateProvidersConfig();

        // Copy migration files
        $this->copyMigrations();

        $this->info('PasskeyAuth package installed successfully.');
    }

    /**
     * Update the Fortify configuration.
     *
     * This method modifies the fortify.php configuration file to comment out certain features and enable profile updates.
     *
     * @return void
     */
    protected function updateFortifyConfig()
    {
        $this->info('Updating fortify.php config...');

        $path = config_path('fortify.php');
        $contents = file_get_contents($path);

        // Define search and replace arrays for configuration changes
        $search = [
            'Features::registration()',
            'Features::resetPasswords()',
            'Features::emailVerification()',
            'Features::updateProfileInformation()',
            'Features::updatePasswords()',
            'Features::twoFactorAuthentication([',
            'confirm' => true,
            'confirmPassword' => true,
            "'window' => 0,",
            ']),'
        ];
        $replace = [
            '//Features::registration()',
            '//Features::resetPasswords()',
            '//Features::emailVerification()',
            'Features::updateProfileInformation()',
            '//Features::updatePasswords()',
            '/*Features::twoFactorAuthentication([',
            "'confirm' => true,",
            "'confirmPassword' => true,",
            "// 'window' => 0,",
            ']),*/'
        ];

        // Perform replacements in the configuration file
        $contents = str_replace($search, $replace, $contents);

        file_put_contents($path, $contents);
    }

    /**
     * Update the Jetstream configuration.
     *
     * This method modifies the jetstream.php configuration file to comment out certain features and enable profile photos.
     *
     * @return void
     */
    protected function updateJetstreamConfig()
    {
        $this->info('Updating jetstream.php config...');

        $path = config_path('jetstream.php');
        $contents = file_get_contents($path);

        // Define search and replace arrays for configuration changes
        $search = [
            'Features::termsAndPrivacyPolicy()',
            'Features::profilePhotos()',
            'Features::api()',
            'Features::teams([',
            'invitations' => true,
            'Features::accountDeletion()'
        ];
        $replace = [
            '// Features::termsAndPrivacyPolicy()',
            'Features::profilePhotos()',
            '//Features::api()',
            'Features::teams([',
            'invitations' => true,
            'Features::accountDeletion()'
        ];

        // Perform replacements in the configuration file
        $contents = str_replace($search, $replace, $contents);

        file_put_contents($path, $contents);
    }

    /**
     * Update the Auth configuration.
     *
     * This method modifies the auth.php configuration file to use the eloquent-webauthn driver.
     *
     * @return void
     */
    protected function updateAuthConfig()
    {
        $this->info('Updating auth.php config...');

        $path = config_path('auth.php');
        $contents = file_get_contents($path);

        // Define search and replace arrays for configuration changes
        $search = [
            "'providers' => [",
            "'driver' => 'eloquent',",
            "'model' => env('AUTH_MODEL', App\Models\User::class),"
        ];
        $replace = [
            "'providers' => [",
            "'driver' => 'eloquent-webauthn',",
            "'model' => env('AUTH_MODEL', App\Models\User::class),",
            "'password_fallback' => true,",
            "/*'driver' => 'eloquent',",
            "'model' => env('AUTH_MODEL', App\Models\User::class),*/"
        ];

        // Perform replacements in the configuration file
        $contents = str_replace($search, $replace, $contents);

        file_put_contents($path, $contents);
    }

    /**
     * Update the app.php configuration.
     *
     * This method modifies the bootstrap/app.php configuration file to set up CSRF token validation and guest redirects.
     *
     * @return void
     */
    protected function updateAppConfig()
    {
        $this->info('Updating app.php config...');

        $path = base_path('bootstrap/app.php');
        $contents = file_get_contents($path);

        // Update CSRF token validation middleware
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

        // Update guest redirect middleware
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

    /**
     * Update the welcome.blade.php view.
     *
     * This method modifies the welcome.blade.php file to change the login route to the passkey route.
     *
     * @return void
     */
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

    /**
     * Update the providers in app.php configuration.
     *
     * This method adds the PasskeyAuthServiceProvider to the bootstrap/app.php configuration file.
     *
     * @return void
     */
    protected function updateProvidersConfig()
    {
        $this->info('Updating providers in app.php config...');

        $path = base_path('bootstrap/app.php');
        $contents = file_get_contents($path);

        $search = '];';
        $replace = "Hoang79\PasskeyAuth\PasskeyAuthServiceProvider::class,
        ];";

        // Perform the replacement to add the service provider
        $contents = str_replace($search, $replace, $contents);

        file_put_contents($path, $contents);
    }

    /**
     * Copy migration files.
     *
     * This method copies the necessary migration files from the package to the application's migration directory.
     *
     * @return void
     */
    protected function copyMigrations()
    {
        $this->info('Copying migration files...');

        $filesystem = new Filesystem;
        $sourcePath = __DIR__ . '/../../../database/migrations/';
        $destinationPath = database_path('migrations/');

        $files = [
            '2024_07_09_100744_create_temporary_email_otps_table.php',
            '2024_07_17_111856_authenticators.php',
        ];

        // Copy each file to the destination path
        foreach ($files as $file) {
            $filesystem->copy($sourcePath . $file, $destinationPath . $file);
        }
    }
}

