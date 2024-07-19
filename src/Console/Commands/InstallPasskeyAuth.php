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
        $this->updateProvidersConfig();
        $this->copyMigrations();

        $this->info('PasskeyAuth package installed successfully.');
    }

    protected function updateFortifyConfig()
    {
        $this->info('Updating fortify.php config...');

        $path = config_path('fortify.php');
        $contents = file_get_contents($path);

        $changes = [
            'Features::registration(),' => '//Features::registration(),',
            'Features::resetPasswords(),' => '//Features::resetPasswords(),',
            'Features::emailVerification(),' => '//Features::emailVerification(),',
            'Features::updateProfileInformation(),' => 'Features::updateProfileInformation(),',
            'Features::updatePasswords(),' => '//Features::updatePasswords(),',
            'Features::twoFactorAuthentication([' . PHP_EOL .
            "            'confirm' => true," . PHP_EOL .
            "            'confirmPassword' => true," . PHP_EOL .
            "            // 'window' => 0," . PHP_EOL .
            '        ]),' => '/*Features::twoFactorAuthentication([' . PHP_EOL .
                "            'confirm' => true," . PHP_EOL .
                "            'confirmPassword' => true," . PHP_EOL .
                "            // 'window' => 0," . PHP_EOL .
                '        ]),*/'
        ];

        foreach ($changes as $search => $replace) {
            $contents = str_replace($search, $replace, $contents);
        }

        $backup = "/**\n* Liste von Passkey hinzugefügt\n*/\n";
        foreach ($changes as $search => $replace) {
            $backup .= str_replace("//", "", $replace) . "\n";
        }

        $backup .= "/**\n* Backup: Originale auskommentiert\n*/\n";
        foreach ($changes as $search => $replace) {
            $backup .= "// " . $search . "\n";
        }

        file_put_contents($path, $backup . $contents);
    }

    protected function updateJetstreamConfig()
    {
        $this->info('Updating jetstream.php config...');

        $path = config_path('jetstream.php');
        $contents = file_get_contents($path);

        $changes = [
            'Features::termsAndPrivacyPolicy(),' => '// Features::termsAndPrivacyPolicy(),',
            'Features::profilePhotos(),' => 'Features::profilePhotos(),',
            'Features::api(),' => '//Features::api(),',
            "Features::teams(['invitations' => true'])" => "Features::teams(['invitations']) => true",
            'Features::accountDeletion(),' => 'Features::accountDeletion(),'
        ];

        foreach ($changes as $search => $replace) {
            $contents = str_replace($search, $replace, $contents);
        }

        $backup = "/**\n* Liste von Passkey hinzugefügt\n*/\n";
        foreach ($changes as $search => $replace) {
            $backup .= str_replace("//", "", $replace) . "\n";
        }

        $backup .= "/**\n* Backup: Originale auskommentiert\n*/\n";
        foreach ($changes as $search => $replace) {
            $backup .= "// " . $search . "\n";
        }

        file_put_contents($path, $backup . $contents);
    }

    protected function updateAuthConfig()
    {
        $this->info('Updating auth.php config...');

        $path = config_path('auth.php');
        $contents = file_get_contents($path);

        // Backup the original 'users' provider
        $contents = str_replace(
            "'users' => [\n            'driver' => 'eloquent',\n            'model' => env('AUTH_MODEL', App\Models\User::class),\n        ],",
            "/*\n'users' => [\n            'driver' => 'eloquent',\n            'model' => env('AUTH_MODEL', App\Models\User::class),\n        ],\n*/",
            $contents
        );

        // Add the new 'users' provider for PasskeyAuth
        $newProvider = "'users' => [\n            'driver' => 'eloquent-webauthn',\n            'model' => env('AUTH_MODEL', App\Models\User::class),\n            'password_fallback' => true,\n        ],\n";

        $backup = "/**\n* Liste von Passkey hinzugefügt\n*/\n" . $newProvider . "\n/**\n* Backup: Originale auskommentiert\n*/\n" . "/*\n'users' => [\n            'driver' => 'eloquent',\n            'model' => env('AUTH_MODEL', App\Models\User::class),\n        ],\n*/";

        $contents = str_replace("'providers' => [", "'providers' => [\n" . $newProvider, $contents);

        file_put_contents($path, $backup . $contents);
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

        // Add service provider
        if (strpos($contents, "Hoang\PasskeyAuth\PasskeyAuthServiceProvider::class") === false) {
            $search = "return [";
            $replace = "return [\n    Hoang\PasskeyAuth\PasskeyAuthServiceProvider::class,";
            $contents = str_replace($search, $replace, $contents);
        }

        file_put_contents($path, $contents);
    }

    protected function updateWelcomeView()
    {
        $this->info('Updating welcome.blade.php view...');

        $path = resource_path('views/welcome.blade.php');
        $contents = file_get_contents($path);

        // Create a backup of the original configuration
        $backup = "/**\n* Backup: Originale auskommentiert\n*/\n" . $contents;

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

        // Add Passkey list comment
        $passkeyComment = "/**\n* Geändert von Passkey\n*/\n" . $contents;
        $contents = $passkeyComment . $contents;

        // Save the backup and the new configuration
        file_put_contents($path, $backup . $contents);
    }


    protected function updateProvidersConfig()
    {
        $this->info('Updating providers.php config...');

        $path = base_path('bootstrap/providers.php');
        $contents = file_get_contents($path);

        // Backup the original configuration
        $backup = "/**\n* Backup: Originale auskommentiert\n*/\n" . $contents;

        $search = 'return [';
        $replace = 'return [
    Hoang79\PasskeyAuth\PasskeyAuthServiceProvider::class,';

        $contents = str_replace($search, $replace, $contents);

        // Add Passkey list comment
        $passkeyComment = "/**\n* Liste von Passkey hinzugefügt\n*/\n" . $contents;
        $contents = $passkeyComment . $contents;

        // Save the backup and the new configuration
        file_put_contents($path, $backup . $contents);
    }



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

        foreach ($files as $file) {
            $filesystem->copy($sourcePath . $file, $destinationPath . $file);
        }

    }
}
