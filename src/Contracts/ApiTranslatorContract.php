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
     * @param string $text The text to be translated
     * @param string $locale Language into which the text should be translated.
     * @param string|null $base_locale Language of the source text to be translated. If omitted, some APIs will attempt to detect the source language automatically.
     * @return string
     */
    public function translate(string $text, string $locale, string $base_locale = null): string;
}
