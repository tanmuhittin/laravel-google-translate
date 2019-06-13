<?php

namespace Tanmuhittin\LaravelGoogleTranslateTests;

abstract class TestCase extends \PHPUnit\Framework\TestCase
{
    public function __construct()
    {
        parent::__construct();
        include_once __DIR__ . './../src/DeclareFunctionsIfMissing.php';
    }
}
