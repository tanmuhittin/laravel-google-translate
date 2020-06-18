<?php

namespace Tanmuhittin\LaravelGoogleTranslate;

use Illuminate\Support\ServiceProvider;
use Tanmuhittin\LaravelGoogleTranslate\Api\GoogleApiTranslate;
use Tanmuhittin\LaravelGoogleTranslate\Api\StichozaApiTranslate;
use Tanmuhittin\LaravelGoogleTranslate\Api\YandexApiTranslate;
use Tanmuhittin\LaravelGoogleTranslate\Commands\TranslateFilesCommand;

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
            __DIR__.'/laravel_google_translate.php' => config_path('laravel_google_translate.php'),
        ]);
    }

    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(TranslatorContract::class,function ($app){
            $config = $app->make('config')->get('laravel_google_translate');
            if($config['google_translate_api_key']!==null){
                return new GoogleApiTranslate($config['google_translate_api_key']);
            }elseif ($config['yandex_translate_api_key']!==null){
                return new YandexApiTranslate($config['yandex_translate_api_key']);
            }else{
                return new StichozaApiTranslate(null);
            }
        });
    }
}
