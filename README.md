# laravel-google-translate
Translate translation files (under /resources/lang) or lang.json files from specified base locale to other languages using stichoza/google-translate-php or Google Translate API https://cloud.google.com/translate/

## installation
```console
composer require tanmuhittin/laravel-google-translate --dev
php artisan vendor:publish --provider=Tanmuhittin\LaravelGoogleTranslate\LaravelGoogleTranslateServiceProvider
```

If you would like to use stichoza/google-translate-php you do not need an API key. If you would like to use Google Translate API, edit config/laravel_google_translate.php and add your Google Translate API key.

```console
php artisan config:cache
```

Then you can run

```console
php artisan translate:files
```

See it on action:<br>

<img src="http://muhittintan.com/tanmuhittin-laravel-google-translate.gif" alt="laravel-google-translate" />

## potential issues

### SSL certificate problem: unable to get local issuer certificate
https://stackoverflow.com/a/31830614

## suggested packages
This package can be used with https://github.com/andrey-helldar/laravel-lang-publisher.

* Add base Laravel translation files using https://github.com/andrey-helldar/laravel-lang-publisher
* Translate your custom files using this package

Done <br>

## todo
* Handle vendor translations too
* Prepare Web Interface
* Add other translation API support (Bing, Yandex...)

## finally
Thank you for using laravel-google-translate :)