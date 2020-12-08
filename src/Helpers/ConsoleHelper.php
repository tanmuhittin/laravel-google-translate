<?php

namespace Tanmuhittin\LaravelGoogleTranslate\Helpers;


trait ConsoleHelper
{
    public function line($text)
    {
        if(!isset($this->verbose) || (isset($this->verbose) && $this->verbose))
            echo $text . "\n";
    }

    public function error($text)
    {
        echo "\033[01;31m" . $text . "\033[0m" . "\n";
    }
}
