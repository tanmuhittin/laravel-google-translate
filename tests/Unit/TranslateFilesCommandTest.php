<?php

namespace Tanmuhittin\LaravelGoogleTranslateTests\Unit;

use Illuminate\Support\Facades\Artisan;
use Tanmuhittin\LaravelGoogleTranslateTests\TestCase;

class TranslateFilesCommandTest extends TestCase
{
    public function testTranslatePhpFilesCommand()
    {
        $this->app->setBasePath(__DIR__.'/../test-resources');
        $this->artisan('translate:files')
            ->expectsQuestion('What is base locale?', 'sv')
            ->expectsQuestion('What are the target locales? Comma seperate each lang key', 'tr')
            ->expectsQuestion('Force overwrite existing translations?','1')
            ->expectsQuestion('Verbose each translation?','1')
            ->expectsQuestion('Use text exploration and json translation or php files?','php')
            ->expectsQuestion('Are there specific target files to translate only? ex: file1,file2','')
            ->expectsQuestion('Are there specific files to exclude?','')
            ->assertExitCode(0);
        $this->assertFileExists(resource_path('lang/tr/tests.php'));
        unlink(resource_path('lang/tr/tests.php'));
        rmdir(resource_path('lang/tr'));
    }

    public function testTranslateJsonFilesCommand()
    {
        $this->app->setBasePath(__DIR__.'/../test-resources');
        $this->artisan('translate:files')
            ->expectsQuestion('What is base locale?', 'sv')
            ->expectsQuestion('What are the target locales? Comma seperate each lang key', 'tr')
            ->expectsQuestion('Force overwrite existing translations?','Yes')
            ->expectsQuestion('Verbose each translation?','Yes')
            ->expectsQuestion('Use text exploration and json translation or php files?','json')
            ->assertExitCode(0);
        $this->assertFileExists(resource_path('lang/tr.json'));
        unlink(resource_path('lang/tr.json'));
    }
}

