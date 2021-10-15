<?php

namespace Tanmuhittin\LaravelGoogleTranslate\Commands;

use Illuminate\Console\Command;
use Tanmuhittin\LaravelGoogleTranslate\TranslationFileTranslators\JsonArrayFileTranslator;
use Tanmuhittin\LaravelGoogleTranslate\TranslationFileTranslators\PhpArrayFileTranslator;
use Tanmuhittin\LaravelGoogleTranslate\Contracts\ApiTranslatorContract;

class TranslateFilesCommand extends Command
{
    public $base_locale;
    public $locales;
    public $excluded_files;
    public $target_files;
    public $json;
    public $force;
    public $verbose;

    protected $translator;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'translate:files';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Translate Translation files. translate:files';

    /**
     * TranslateFilesCommand constructor.
     * @param ApiTranslatorContract $translator
     * @param string $base_locale
     * @param string $locales
     * @param string $target_files
     * @param bool $force
     * @param bool $json
     * @param bool $verbose
     * @param string $excluded_files
     */
    public function __construct($base_locale = 'en', $locales = 'tr,it', $target_files = '', $force = false, $json = false, $verbose = true, $excluded_files = 'auth,pagination,validation,passwords')
    {
        parent::__construct();
        $this->base_locale = $base_locale;
        $this->locales = array_filter(explode(",", $locales));
        $this->target_files = array_filter(explode(",", $target_files));
        $this->force = $force;
        $this->json = $json;
        $this->verbose = $verbose;
        $this->excluded_files = array_filter(explode(",", $excluded_files));
    }

    /**
     * @throws \Exception
     */
    public function handle()
    {
        //Collect input
        $this->base_locale = $this->ask('What is base locale?', config('app.locale', 'en'));
        $this->locales = array_filter(explode(",", $this->ask('What are the target locales? Comma seperate each lang key', config('laravel_google_translate.default_target_locales','tr,it'))));
        $should_force = $this->choice('Force overwrite existing translations?', ['No', 'Yes'], 'No');
        $this->force = false;
        if ($should_force === 'Yes') {
            $this->force = true;
        }
        $should_verbose = $this->choice('Verbose each translation?', ['No', 'Yes'], 'Yes');
        $this->verbose = false;
        if ($should_verbose === 'Yes') {
            $this->verbose = true;
        }
        $mode = $this->choice('Use text exploration and json translation or php files?', ['json', 'php'], 'php');
        $this->json = false;
        if ($mode === 'json') {
            $this->json = true;
            $file_translator = new JsonArrayFileTranslator($this->base_locale, $this->verbose, $this->force);
        }
        else {
            $file_translator = new PhpArrayFileTranslator($this->base_locale, $this->verbose, $this->force);
            $this->target_files = array_filter(explode(",", $this->ask('Are there specific target files to translate only? ex: file1,file2', '')));
            foreach ($this->target_files as $key => $target_file) {
                $this->target_files[$key] = $target_file;
            }
            $file_translator->setTargetFiles($this->target_files);
            $this->excluded_files = array_filter(explode(",", $this->ask('Are there specific files to exclude?', 'auth,pagination,validation,passwords')));
            $file_translator->setExcludedFiles($this->excluded_files);
        }
        //Start Translating
        $bar = $this->output->createProgressBar(count($this->locales));
        $bar->start();
        $this->line("");
        // loop target locales
        foreach ($this->locales as $locale) {
            if ($locale == $this->base_locale) {
                continue;
            }
            $this->line($this->base_locale . " -> " . $locale . " translating...");
            $file_translator->handle($locale);
            $this->line($this->base_locale . " -> " . $locale . " translated.");
            $bar->advance();
            $this->line("");
        }
        $bar->finish();
        $this->line("");
        $this->line("Translations Completed.");
    }
}
