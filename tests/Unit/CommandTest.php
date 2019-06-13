<?php

namespace Tanmuhittin\LaravelGoogleTranslateTests\Unit;

use Tanmuhittin\LaravelGoogleTranslate\Commands\TranslateFilesCommand;
use Tanmuhittin\LaravelGoogleTranslateTests\TestCase;

class CommandTest extends TestCase
{
    public function testTranslatePhpFilesCommand()
    {
        $to_be_translated_texts_sv = trans('tests', [], 'sv');
        $command = new TranslateFilesCommand('sv', 'tr,it', '', $force = false, $json = false, $verbose = false, $excluded_files = 'auth,pagination,validation,passwords');
        $command->translate_php_array_files('tr');
        $command->translate_php_array_files('it');
        $translated_texts_tr = trans('tests', [], 'tr');
        $translated_texts_it = trans('tests', [], 'it');
        deleteAll(__DIR__ . '/../../test-resources/resources/lang/tr');
        deleteAll(__DIR__ . '/../../test-resources/resources/lang/it');
        $this->assertEquals(count($to_be_translated_texts_sv), count($translated_texts_tr));
        $this->assertEquals(count($to_be_translated_texts_sv), count($translated_texts_it));
    }

    public function testTextExplorationAndJsonTranslationsCommand()
    {
        $command = new TranslateFilesCommand('en', 'tr,it', '', $force = false, $json = true, $verbose = false, $excluded_files = 'auth,pagination,validation,passwords');
        $stringKeys = $command->explore_strings();
        $command->translate_json_array_file('tr', $stringKeys);
        $command->translate_json_array_file('it', $stringKeys);
        $tr_translations = json_decode(file_get_contents(__DIR__ . '/../../test-resources/resources/lang/tr.json'), true);
        $it_translations = json_decode(file_get_contents(__DIR__ . '/../../test-resources/resources/lang/it.json'), true);
        unlink(__DIR__ . '/../../test-resources/resources/lang/tr.json');
        unlink(__DIR__ . '/../../test-resources/resources/lang/it.json');
        $this->assertNotEquals(0, count($tr_translations));
        $this->assertNotEquals(0, count($it_translations));
    }
}
