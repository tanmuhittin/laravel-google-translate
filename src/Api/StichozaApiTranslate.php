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
        if ($base_locale === null)
            $this->handle->setSource();
        else
            $this->handle->setSource($base_locale);
        $this->handle->setTarget($locale);
        try {
            return $this->handle->translate($text);
        } catch (\ErrorException $e) {
            return false;
        }
    }
}
