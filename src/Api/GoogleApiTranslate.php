<?php

namespace Tanmuhittin\LaravelGoogleTranslate\Api;

use Google\Cloud\Translate\V2\TranslateClient;
use Tanmuhittin\LaravelGoogleTranslate\TranslatorContract;

class GoogleApiTranslate implements TranslatorContract
{
    public $handle;

    public function __construct($api_key)
    {
        $this->handle = new TranslateClient([
            'key' => $api_key
        ]);

    }

    public function translate(string $text, string $locale, string $base_locale): string
    {
        if(is_null($base_locale))
            $result = $this->handle->translate($text, [
                'target' => $locale
            ]);
        else
            $result = $this->handle->translate($text, [
                'source' => $base_locale,
                'target' => $locale
            ]);

        return $result['text'];
    }
}
