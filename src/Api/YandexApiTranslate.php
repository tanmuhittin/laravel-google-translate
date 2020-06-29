<?php

namespace Tanmuhittin\LaravelGoogleTranslate\Api;


use Tanmuhittin\LaravelGoogleTranslate\Contracts\ApiTranslatorContract;

class YandexApiTranslate implements ApiTranslatorContract
{
    public $handle;


    public function __construct($api_key)
    {
        $this->handle = new \Yandex\Translate\Translator($api_key);

    }

    public function translate(string $text, string $locale, string $base_locale = null): string
    {
        try {
            $translation = $this->handle->translate($text, $base_locale . '-' . $locale);
        } catch (\Exception $e) {
            return false;
        }
        return $translation['text'][0]; //todo test if works Yandex code is old
    }
}
