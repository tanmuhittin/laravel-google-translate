<?php

namespace Tanmuhittin\LaravelGoogleTranslate\Contracts;


interface FileTranslatorContract
{
    public function __construct($base_locale, $verbose=true, $force=false);

    public function handle($target_locale) : void;
}
