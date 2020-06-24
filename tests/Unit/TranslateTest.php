<?php

namespace Tanmuhittin\LaravelGoogleTranslateTests\Unit;

use Illuminate\Support\Str;
use Tanmuhittin\LaravelGoogleTranslateTests\TestCase;

class TranslateTest extends TestCase
{
    public function testTranslate()
    {
        $test_text = 'Hello World';
        $translated_test_text = Str::apiTranslate($test_text, 'tr', 'en');
        $this->assertStringContainsStringIgnoringCase('Dünya', $translated_test_text);
    }

    public function testTranslateWithAttributes(){
        $test_text = 'My name is :attribute';
        $translated_test_text = Str::apiTranslateWithAttributes($test_text, 'tr', 'en');
        $this->assertStringContainsString(':attribute', $translated_test_text);
    }
}

