<?php

namespace Tanmuhittin\LaravelGoogleTranslate\Contracts;


interface ApiTranslatorContract
{

    /**
     * Define the translator class or connect to api service
     * @param $api_key
     * @return void
     */
    public function __construct($api_key);


    /**
     * @param string $text
     * @param string $locale
     * @param string|null $base_locale
     * @return string
     */
    public function translate(string $text, string $locale, string $base_locale = null): string;
}
