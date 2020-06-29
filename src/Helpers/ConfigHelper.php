<?php

namespace Tanmuhittin\LaravelGoogleTranslate\Helpers;


class ConfigHelper
{
    public static function getLaravelGoogleTranslateConfig(){
        $config = resolve('config')->get('laravel_google_translate');
        if(!isset($config['api_limit_settings'])){
            $config['api_limit_settings'] = [
                'no_requests_per_batch' => 5,
                'sleep_time_between_batches' => 1
            ];
        }
        return $config;
    }

    public static function getBaseLocale($base_locale){
        if ($base_locale === null) {
            $config = resolve('config');
            if ($config['locale'] !== null) {
                $base_locale = $config['locale'];
            }
        }
        return $base_locale;
    }
}