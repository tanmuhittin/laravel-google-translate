<?php
namespace Tanmuhittin\LaravelGoogleTranslate\Helpers;


class FileHelper
{
    public static function getFile($fileAddress)
    {
        if (file_exists(resource_path('lang/')))
        {
            return resource_path('lang/' . $fileAddress);
        }
        if (file_exists(base_path('lang/')))
        {
            return base_path('lang/' . $fileAddress);
        }

        throw new \Exception("Language folder cannot be found");
    }
}