<?php

namespace Tanmuhittin\LaravelGoogleTranslate;

use Illuminate\Support\ServiceProvider;
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
        if ($this->app['config']->get('laravel_google_translate') === null) {
            $this->app['config']->set('laravel_google_translate', require __DIR__.'/laravel_google_translate.php');
        }
    }
}
