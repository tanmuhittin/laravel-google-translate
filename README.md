# laravel-google-translate
Translate translation files (under /resources/lang) from specified base locale to all other available languages using google translate api

## installation
```console
composer require tanmuhittin/laravel-google-translate
php artisan vendor:publish --provider=Tanmuhittin\LaravelGoogleTranslate\LaravelGoogleTranslateServiceProvider
```

Edit config/laravel_google_translate.php and add your Google Translate API key.

```console
php artisan config:cache
```

Then you can run

```console
php artisan translate:files
```
or specify excluded files and base locale
```console
php artisan translate:files --baselocale=tr --exclude=auth,passwords
```
## potential issues

### SSL certificate problem: unable to get local issuer certificate
https://stackoverflow.com/a/31830614

## suggested packages
This package can be used with https://github.com/andrey-helldar/laravel-lang-publisher where you have additional translation files to be translated to all other languages

## todo
* Specify target locales
* Add option to prevent overwrite or visa versa
* Google Translate API comes with limits. This package translates each item one by one. Find a way to bulk translate.

## finally
Thank you for using laravel-google-translate :)
