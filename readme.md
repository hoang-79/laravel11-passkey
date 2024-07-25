# Laravel 11 Passkey Authentication

**English version below.**

---

## Voraussetzungen

- PHP [8.2]
- Laravel [11]
- Livewire [3.0]
- Jetstream [5.1]
- Datenbank (erste Migration bereits ausgeführt)

## Einführung

Dieses Paket ermöglicht die Verwendung von Passkeys zur Authentifizierung in Laravel 11 Anwendungen. Passkeys sind eine benutzerfreundliche und sichere Alternative zu herkömmlichen Passwörtern. Sie können auf Mobilgeräten und in Passwortmanagern gespeichert werden und ermöglichen eine bequeme und sichere Anmeldung ohne Passwort.

### Vorteile von Passkeys

- **Benutzerfreundlichkeit**: Kein Bedarf mehr, sich komplexe Passwörter zu merken.
- **Sicherheit**: Verhindert Phishing-Angriffe und Passwortdiebstahl.
- **Kompatibilität**: Funktioniert mit gängigen Passwortmanagern und mobilen Geräten.

## Installationsanleitung

### Paket installieren

1. Fügen Sie das Paket zu Ihrem Laravel-Projekt hinzu:

    ```bash
    composer require hoang79/passkeyauth
    ```

2. Führen Sie den Passkey-Installer aus, um die notwendigen Anpassungen vorzunehmen:

    ```bash
    php artisan passkey:install
    ```

### Manuelle Anpassungen

1. Bearbeiten Sie die `.env` Datei:
   - Stellen Sie sicher, dass bei der `APP_URL` kein `http(s)://` steht, sondern nur die Domain. Zum Beispiel:

    ```env
    APP_URL=laravel-passkey3.test
    ```

2. Passen Sie den Redirect bei der folgenden Zeile in `config/fortify.php` an:

    ```php
    'home' => '/dashboard',
    ```

3. Passen Sie die Mail-Einstellungen in der `.env` Datei an, damit der OTP-Code versendet werden kann:

    ```env
    MAIL_MAILER=smtp
    MAIL_HOST=127.0.0.1
    MAIL_PORT=2525
    MAIL_USERNAME="${APP_NAME}"
    MAIL_PASSWORD=null
    MAIL_ENCRYPTION=null
    MAIL_FROM_ADDRESS="hello@example.com"
    MAIL_FROM_NAME="${APP_NAME}"
    ```

4. Stellen Sie sicher, dass die notwendigen Migrationen ausgeführt wurden:

    ```bash
    php artisan migrate
    ```

## Nutzung

Nach der Installation und den notwendigen Anpassungen können Benutzer sich mit Passkeys registrieren und anmelden. Die Registrierung erfolgt über die Eingabe einer E-Mail-Adresse und das Erhalten eines OTP (One Time Password), gefolgt von der Speicherung des Passkeys.

Die Anmeldung erfolgt ebenfalls über die Eingabe der E-Mail-Adresse und das Verwenden des gespeicherten Passkeys.

## Zukünftige Features

- OTP nochmals versenden
- Mit Mobilnummer und SMS anmelden

## Weitere Informationen

Weitere Informationen zu Passkeys und deren Verwendung finden Sie in der offiziellen Dokumentation zu WebAuthn und den Passkey-Standards.

---

# Laravel 11 Passkey Authentication

## Requirements

- PHP [8.2]
- Laravel [11]
- Livewire [3.0]
- Jetstream [5.1]
- Database (initial migration already run)

## Introduction

This package enables the use of passkeys for authentication in Laravel 11 applications. Passkeys are a user-friendly and secure alternative to traditional passwords. They can be stored on mobile devices and password managers, allowing for convenient and secure passwordless login.

### Benefits of Passkeys

- **User-Friendly**: No need to remember complex passwords.
- **Security**: Prevents phishing attacks and password theft.
- **Compatibility**: Works with popular password managers and mobile devices.

## Installation Guide

### Install the Package

1. Add the package to your Laravel project:

    ```bash
    composer require hoang79/passkeyauth
    ```

2. Run the Passkey installer to make the necessary adjustments:

    ```bash
    php artisan passkey:install
    ```

### Manual Adjustments

1. Edit the `.env` file:
   - Ensure that the `APP_URL` does not contain `http(s)://`, only the domain. For example:

    ```env
    APP_URL=laravel-passkey3.test
    ```

2. Adjust the redirect in `config/fortify.php`:

    ```php
    'home' => '/dashboard',
    ```

3. Update the mail settings in the `.env` file to enable OTP code sending:

    ```env
    MAIL_MAILER=smtp
    MAIL_HOST=127.0.0.1
    MAIL_PORT=2525
    MAIL_USERNAME="${APP_NAME}"
    MAIL_PASSWORD=null
    MAIL_ENCRYPTION=null
    MAIL_FROM_ADDRESS="hello@example.com"
    MAIL_FROM_NAME="${APP_NAME}"
    ```

4. Ensure the necessary migrations have been executed:

    ```bash
    php artisan migrate
    ```

## Usage

After installation and necessary adjustments, users can register and log in using passkeys. Registration involves entering an email address, receiving a One Time Password (OTP), and saving the passkey.

Logging in involves entering the email address and using the saved passkey.

## Future Features

- Resend OTP
- Sign in with mobile number and SMS

## Further Information

For more information on passkeys and their use, refer to the official WebAuthn documentation and passkey standards.

---

## License

This package is licensed under the MIT License. For more details, see the [LICENSE](LICENSE) file.
