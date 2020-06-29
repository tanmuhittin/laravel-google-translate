<?php

namespace Tanmuhittin\LaravelGoogleTranslate\Translators;


use Tanmuhittin\LaravelGoogleTranslate\Contracts\ApiTranslatorContract;
use Tanmuhittin\LaravelGoogleTranslate\Helpers\ApiLimitHelper;

class ApiTranslate
{
    use ApiLimitHelper;

    protected $translator;

    public function __construct(ApiTranslatorContract $translator, $request_per_second, $sleep_for_sec)
    {
        $this->translator = $translator;
        $this->request_per_sec = $request_per_second;
        $this->sleep_for_sec = $sleep_for_sec;
    }

    public function translate($text, $locale, $base_locale = null) : string
    {
        $this->api_limit_check();
        return $this->translator->translate($text, $locale, $base_locale);
    }
}