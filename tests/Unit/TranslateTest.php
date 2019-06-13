<?php

namespace Tanmuhittin\LaravelGoogleTranslateTests\Unit;

use Tanmuhittin\LaravelGoogleTranslate\Commands\TranslateFilesCommand;
use Tanmuhittin\LaravelGoogleTranslateTests\TestCase;

class TranslateTest extends TestCase
{
    public function testTranslate()
    {
        $test_text = "Hello :yourname";
        $translated_test_text = TranslateFilesCommand::translate("en", "tr", $test_text);
        $this->assertStringContainsString(":yourname", $translated_test_text);
    }
}
