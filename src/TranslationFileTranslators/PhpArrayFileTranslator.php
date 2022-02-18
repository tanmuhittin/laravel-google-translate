<?php

namespace Tanmuhittin\LaravelGoogleTranslate\TranslationFileTranslators;

use Illuminate\Support\Str;
use Tanmuhittin\LaravelGoogleTranslate\Contracts\FileTranslatorContract;
use Tanmuhittin\LaravelGoogleTranslate\Helpers\ConsoleHelper;
use Tanmuhittin\LaravelGoogleTranslate\Helpers\FileHelper;

class PhpArrayFileTranslator implements FileTranslatorContract
{
    use ConsoleHelper;
    private $base_locale;
    private $target_files;
    private $excluded_files;
    private $verbose;
    private $force;

    public function __construct($base_locale, $verbose = true, $force = false)
    {
        $this->base_locale = $base_locale;
        $this->verbose = $verbose;
        $this->force = $force;
    }

    public function handle($target_locale) : void
    {
        $files = $this->get_translation_files();
        $this->create_missing_target_folders($target_locale, $files);
        foreach ($files as $file) {
            $existing_translations = [];
            $file_address = $this->get_language_file_address($target_locale, $file.'.php');
            $this->line($file_address.' is preparing');
            if (file_exists($file_address)) {
                $this->line('File already exists');
                $existing_translations = trans($file, [], $target_locale);
                $this->line('Existing translations collected');
            }
            $to_be_translateds = trans($file, [], $this->base_locale);
            $this->line('Source text collected');
            $translations = [];
            if (is_array($to_be_translateds)) {
                $translations = $this->handleTranslations($to_be_translateds, $existing_translations, $target_locale);
            }
            $this->write_translations_to_file($target_locale, $file, $translations);
        }
        return;
    }

    // file, folder operations:

    private function create_missing_target_folders($target_locale, $files)
    {
        $target_locale_folder = $this->get_language_file_address($target_locale);
        if(!is_dir($target_locale_folder)){
            mkdir($target_locale_folder);
        }
        foreach ($files as $file){
            if(Str::contains($file, '/')){
                $folder_address = $this->get_language_file_address($target_locale, dirname($file));
                if(!is_dir($folder_address)){
                    mkdir($folder_address, 0777, true);
                }
            }
        }
    }

    private function write_translations_to_file($target_locale, $file, $translations){
        $file = fopen($this->get_language_file_address($target_locale, $file.'.php'), "w+");
        $export = var_export($translations, true);

        //use [] notation instead of array()
        $patterns = [
            "/array \(/" => '[',
            "/^([ ]*)\)(,?)$/m" => '$1]$2',
            "/=>[ ]?\n[ ]+\[/" => '=> [',
            "/([ ]*)(\'[^\']+\') => ([\[\'])/" => '$1$2 => $3',
        ];
        $export = preg_replace(array_keys($patterns), array_values($patterns), $export);


        $write_text = "<?php \nreturn " . $export . ";";
        fwrite($file, $write_text);
        fclose($file);
        return 1;
    }

    private function get_language_file_address($locale, $sub_folder = null){
        return $sub_folder!==null ?
            FileHelper::getFile($locale.'/'.$sub_folder) :
            FileHelper::getFile($locale);
    }

    private function strip_php_extension($filename){
        if(substr($filename,-4) === '.php'){
            $filename = substr($filename,0, -4);
        }
        return $filename;
    }

    private function get_translation_files($folder = null){
        if (count($this->target_files) > 0) {
            $files = $this->target_files;
        }
        else{
            $files = [];
            $dir_contents = preg_grep('/^([^.])/', scandir($this->get_language_file_address($this->base_locale, $folder)));
            foreach ($dir_contents as $dir_content){
                if(!is_null($folder))
                    $dir_content = $folder.'/'.$dir_content;
                if (in_array($this->strip_php_extension($dir_content), $this->excluded_files)) {
                    continue;
                }
                if(is_dir($this->get_language_file_address($this->base_locale, $dir_content))){
                    $files = array_merge($files,$this->get_translation_files($dir_content));
                }
                else{
                    $files[] = $this->strip_php_extension($dir_content);
                }
            }
        }
        return $files;
    }


    // in file operations :

    /**
     * Walks array recursively to find and translate strings
     *
     * @param array $to_be_translateds
     * @param array $existing_translations
     * @param String $target_locale
     *
     * @return array
     */
    private function handleTranslations($to_be_translateds, $existing_translations, $target_locale)
    {
        $translations = [];
        foreach ($to_be_translateds as $key => $to_be_translated) {
            if (is_array($to_be_translated)) {
                if (!isset($existing_translations[$key])) {
                    $existing_translations[$key] = [];
                }
                $translations[$key] = $this->handleTranslations($to_be_translated, $existing_translations[$key], $target_locale);
            } else {
                if (isset($existing_translations[$key]) && $existing_translations[$key] != '' && !$this->force) {
                    $translations[$key] = $existing_translations[$key];
                    $this->line('Exists Skipping -> ' . $to_be_translated . ' : ' . $translations[$key]);
                    continue;
                } else {
                    $translations[$key] = Str::apiTranslateWithAttributes($to_be_translated, $target_locale, $this->base_locale);
                    $this->line($to_be_translated . ' : ' . $translations[$key]);
                }
            }
        }
        return $translations;
    }

    // others

    public function setTargetFiles($target_files)
    {
        $this->target_files = $target_files;
    }

    public function setExcludedFiles($excluded_files)
    {
        $this->excluded_files = $excluded_files;
    }
}
