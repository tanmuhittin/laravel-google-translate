<?php

namespace Tanmuhittin\LaravelGoogleTranslate;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Tanmuhittin\LaravelGoogleTranslate\Api\GoogleApiTranslate;
use Tanmuhittin\LaravelGoogleTranslate\Api\StichozaApiTranslate;
use Tanmuhittin\LaravelGoogleTranslate\Api\YandexApiTranslate;
use Tanmuhittin\LaravelGoogleTranslate\Commands\TranslateFilesCommand;
use Tanmuhittin\LaravelGoogleTranslate\Contracts\ApiTranslatorContract;

class LaravelGoogleTranslateServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->commands([
            TranslateFilesCommand::class
        ]);
        $this->publishes([
            __DIR__ . '/laravel_google_translate.php' => config_path('laravel_google_translate.php'),
        ]);
    }

    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(ApiTranslatorContract::class, function ($app) {
            $config = $app->make('config')->get('laravel_google_translate');
            if ($config['custom_api_translator']!==null){
                $custom_translator = new $config['custom_api_translator']($config['custom_api_translator_key']);
                if($custom_translator instanceof ApiTranslatorContract)
                    return $custom_translator;
                else
                    throw new \Exception($config['custom_api_translator'].' must implement '.ApiTranslatorContract::class);
            }
            elseif ($config['google_translate_api_key'] !== null) {
                return new GoogleApiTranslate($config['google_translate_api_key']);
            } elseif ($config['yandex_translate_api_key'] !== null) {
                return new YandexApiTranslate($config['yandex_translate_api_key']);
            } else {
                return new StichozaApiTranslate(null);
            }
        });

        $this->app->singleton(ApiTranslateWithAttribute::class,function ($app){
            return new ApiTranslateWithAttribute(resolve(ApiTranslatorContract::class));
        });

        Str::macro('apiTranslate', function (string $text, string $locale, string $base_locale = null) {
            if ($base_locale === null) {
                $config = resolve('config')->get('app');
                if (!is_null($config['locale'])) {
                    $base_locale = $config['locale'];
                }
            }
            $translator = resolve(ApiTranslatorContract::class);
            return $translator->translate($text, $locale, $base_locale);
        });
        Str::macro('apiTranslateWithAttributes', function (string $text, string $locale, string $base_locale = null) {
            if ($base_locale === null) {
                $config = resolve('config')->get('app');
                if (!is_null($config['locale'])) {
                    $base_locale = $config['locale'];
                }
            }
            $translator = resolve(ApiTranslateWithAttribute::class);
            return $translator->translate($text, $locale, $base_locale);
        });
    }
}
