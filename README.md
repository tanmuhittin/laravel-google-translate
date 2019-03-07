# laravel-google-translate
Translate translation files (under /resources/lang) and lang.json files from specified base locale to all other available languages using google translate api https://cloud.google.com/translate/

## installation
```console
composer require tanmuhittin/laravel-google-translate --dev
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
or specify base locale, excluded files, target locales. If you like to see the translated texts use --verbose option. --force option enables overwrites to existing target files.
```console
php artisan translate:files --baselocale=tr --exclude=auth,passwords --targetlocales=en,de --verbose --force
```
## potential issues

### SSL certificate problem: unable to get local issuer certificate
https://stackoverflow.com/a/31830614

## suggested packages
This package can be used with https://github.com/andrey-helldar/laravel-lang-publisher.
Example Scenario: <br>
You would like to add another language support where you have other translation files in addition to base translation files of Laravel. So follow the steps below.
* Add base Laravel translation files using https://github.com/andrey-helldar/laravel-lang-publisher
* Translate your custom files using this package

Done <br>

## todo
* Translating one by one takes a long time. Use bulk translate

## finally
Thank you for using laravel-google-translate :)