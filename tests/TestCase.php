<?php

namespace Tanmuhittin\LaravelGoogleTranslateTests;

use Tanmuhittin\LaravelGoogleTranslate\LaravelGoogleTranslateServiceProvider;
use Tests\Laravel\App;

abstract class TestCase extends \Orchestra\Testbench\TestCase
{
    protected function getPackageProviders($app)
    {
        return [LaravelGoogleTranslateServiceProvider::class];
    }
}
