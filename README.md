# laravel-google-translate

* Translate translation files (under /resources/lang) or lang.json files
* Provide extra facade functions Str::apiTranslate and Str::apiTranslateWithAttributes

by using stichoza/google-translate-php or Google Translate API https://cloud.google.com/translate/ or Yandex Translatin API https://tech.yandex.com/translate/

## Str facade api-translation helpers
This package provides two translation methods for Laravel helper Str
* `Illuminate\Support\Str::apiTranslate` -> Translates texts using your selected api in config
* `Illuminate\Support\Str::apiTranslateWithAttributes` -> Again translates texts using your selected api in config
 in addition to that this function ***respects Laravel translation text attributes*** like :name
 
## How to use your own translation API
* Create your own translation API class by implementing `Tanmuhittin\LaravelGoogleTranslate\Contracts\ApiTranslatorContract`.
* Set your classname in `config/laravel_google_translate.php` under the `custom_api_translator` key. 
* Set your custom API key for your custom class in the `custom_api_translator_key` key in the config file, or ideally in the `CUSTOM_API_TRANSLATOR_KEY` environment variable.

Example:<br>
_config/laravel_google_translate.php_
```php
'custom_api_translator' => env('CUSTOM_API_TRANSLATOR', Myclass::class),
'custom_api_translator_key' => env('CUSTOM_API_TRANSLATOR_KEY', null),
```

Now all translations will use your custom api.

## installation
```console
composer require --dev tanmuhittin/laravel-google-translate
php artisan vendor:publish --provider="Tanmuhittin\LaravelGoogleTranslate\LaravelGoogleTranslateServiceProvider"
```

If you would like to use stichoza/google-translate-php you do not need an API key. If you would like to use Google Translate API, edit config/laravel_google_translate.php and add your Google Translate API key.

Then you can run:

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

Done

## finally
Thank you for using laravel-google-translate :)
