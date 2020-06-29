<?php

namespace Tanmuhittin\LaravelGoogleTranslate\Helpers;


trait ApiLimitHelper
{
    //api limit settings
    protected $request_count = 0;
    protected $request_per_sec;
    protected $sleep_for_sec;


    /**
     * Check if the API request limit reached.
     */
    protected function api_limit_check()
    {
        if ($this->request_count >= $this->request_per_sec) {
            sleep($this->sleep_for_sec);
            $this->request_count = 0;
        }
        $this->request_count++;
    }
}