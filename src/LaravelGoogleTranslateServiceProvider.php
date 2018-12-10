<?php

namespace Tanmuhittin\LaravelGoogleTranslate;


class LaravelGoogleTranslateServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                TransateFilesCommand::class
            ]);
        }
        $this->publishes([
            __DIR__.'./laravel_google_translate.php' => config_path('laravel_google_translate.php'),
        ]);
    }
}