<?php

namespace Tanmuhittin\LaravelGoogleTranslate\Api;


use Stichoza\GoogleTranslate\GoogleTranslate;
use Tanmuhittin\LaravelGoogleTranslate\Contracts\ApiTranslatorContract;

class StichozaApiTranslate implements ApiTranslatorContract
{
    public $handle;

    /**
     * No need for an api_key
     * @param null $api_key
     */
    public function __construct($api_key = null)
    {
        $this->handle = new GoogleTranslate();
    }

    public function translate(string $text, string $locale, string $base_locale = null): string
    {
        $this->handle
            ->setSource($base_locale)
            ->setTarget($locale);

        try {
            return $this->handle->translate($text) ?? '';
        } catch (\Exception $e) {
            return false;
        }
    }
}
