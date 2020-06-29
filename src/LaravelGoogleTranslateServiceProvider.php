<?php

namespace Tanmuhittin\LaravelGoogleTranslate;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Tanmuhittin\LaravelGoogleTranslate\Api\GoogleApiTranslate;
use Tanmuhittin\LaravelGoogleTranslate\Api\StichozaApiTranslate;
use Tanmuhittin\LaravelGoogleTranslate\Api\YandexApiTranslate;
use Tanmuhittin\LaravelGoogleTranslate\Commands\TranslateFilesCommand;
use Tanmuhittin\LaravelGoogleTranslate\Contracts\ApiTranslatorContract;
use Tanmuhittin\LaravelGoogleTranslate\Helpers\ConfigHelper;
use Tanmuhittin\LaravelGoogleTranslate\Translators\ApiTranslate;
use Tanmuhittin\LaravelGoogleTranslate\Translators\ApiTranslateWithAttribute;

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
            __DIR__ . '/config/laravel_google_translate.php' => config_path('laravel_google_translate.php'),
        ]);
    }

    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $config = ConfigHelper::getLaravelGoogleTranslateConfig();

        $this->app->singleton(ApiTranslatorContract::class, function ($app) use ($config) {
            if (isset($config['custom_api_translator']) && $config['custom_api_translator']!==null){
                $custom_translator = new $config['custom_api_translator']($config['custom_api_translator_key']);
                if($custom_translator instanceof ApiTranslatorContract)
                    return $custom_translator;
                else
                    throw new \Exception($config['custom_api_translator'].' must implement '.ApiTranslatorContract::class);
            }
            elseif (isset($config['google_translate_api_key']) && $config['google_translate_api_key'] !== null) {
                return new GoogleApiTranslate($config['google_translate_api_key']);
            } elseif (isset($config['yandex_translate_api_key']) && $config['yandex_translate_api_key'] !== null) {
                return new YandexApiTranslate($config['yandex_translate_api_key']);
            } else {
                return new StichozaApiTranslate(null);
            }
        });

        $this->app->singleton(ApiTranslate::class,function ($app) use ($config){
            return new ApiTranslate(
                resolve(ApiTranslatorContract::class),
                $config['api_limit_settings']['no_requests_per_batch'],
                $config['api_limit_settings']['sleep_time_between_batches']
            );
        });


        $this->app->singleton(ApiTranslateWithAttribute::class,function ($app) use ($config){
            return new ApiTranslateWithAttribute(
                resolve(ApiTranslatorContract::class),
                $config['api_limit_settings']['no_requests_per_batch'],
                $config['api_limit_settings']['sleep_time_between_batches']
            );
        });

        Str::macro('apiTranslate', function (string $text, string $locale, string $base_locale = null) {
            ConfigHelper::getBaseLocale($base_locale);
            $translator = resolve(ApiTranslate::class);
            return $translator->translate($text, $locale, $base_locale);
        });
        Str::macro('apiTranslateWithAttributes', function (string $text, string $locale, string $base_locale = null) {
            ConfigHelper::getBaseLocale($base_locale);
            $translator = resolve(ApiTranslateWithAttribute::class);
            return $translator->translateWithAttributes($text, $locale, $base_locale);
        });
    }
}
