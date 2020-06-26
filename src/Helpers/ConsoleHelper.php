<?php

namespace Tanmuhittin\LaravelGoogleTranslate\Helpers;


trait ConsoleHelper
{
    public function line($text)
    {
        if(!isset($this->verbose) || (isset($this->verbose) && $this->verbose))
            echo $text . "\n";
    }
}
