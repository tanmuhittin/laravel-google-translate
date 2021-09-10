<?php

namespace Tanmuhittin\LaravelGoogleTranslate\TranslationFileTranslators;

use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Str;
use Tanmuhittin\LaravelGoogleTranslate\Contracts\FileTranslatorContract;
use Tanmuhittin\LaravelGoogleTranslate\Helpers\ConsoleHelper;

class PhpArrayFileTranslator implements FileTranslatorContract
{
    use ConsoleHelper;
    private $base_locale;
    private $base_locale_path;
    private $target_files;
    private $excluded_files;
    private $verbose;
    private $force;

    public function __construct($base_locale, $verbose = true, $force = false)
    {
        $this->base_locale = $base_locale;
        $this->base_locale_path = $this->to_unix_dir_separator(resource_path('lang/' . $this->base_locale . '/'));
        $this->verbose = $verbose;
        $this->force = $force;
    }

    public function handle($target_locale) : void
    {
        $files = $this->get_translation_files();
        $this->create_missing_target_folders($target_locale, $files);
        foreach ($files as $file) {
            $existing_translations = [];
            $group_to_translate = $this->file_to_namespace($file);
            $file_address = $this->get_language_file_address($target_locale, $file);
            $this->line($file_address.' is preparing');
            if (file_exists($file_address)) {
                $this->line('File already exists');
                $existing_translations = trans($group_to_translate, [], $target_locale);
                $this->line('Existing translations collected');
            }
            $to_be_translateds = trans($group_to_translate, [], $this->base_locale);
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
        if (! is_dir($target_locale_folder)) {
            mkdir($target_locale_folder);
        }

        foreach ($files as $file) {
            if (Str::contains($file, '/')) {
                $folder_address = $this->get_language_file_address($target_locale, dirname($file));
                if (! is_dir($folder_address)) {
                    mkdir($folder_address, 0777, true);
                }
            }
        }
    }

    private function write_translations_to_file($target_locale, $file, $translations)
    {
        $target = $this->get_language_file_address($target_locale, $file);
        $file   = fopen($target, "w+");
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

    private function get_language_file_address($locale, $sub_folder = null)
    {
        // We replace the first base locale code ocurrence and change it to the target locale code, this
        // allows to us to use it with package translations (under /lang/vendor)
        $dest_path = Str::replaceFirst(
            '/' . $this->base_locale . '/',
            '/' . $locale . '/',
            (empty($sub_folder) ? $this->base_locale_path : $sub_folder)
        );

        return $dest_path;
    }

    private function strip_php_extension($filename)
    {
        if (substr($filename, -4) === '.php') {
            $filename = substr($filename, 0, -4);
        }
        return $filename;
    }

    private function get_translation_files()
    {

        if (count($this->target_files) > 0) {
            return $this->target_files;
        }

        $lang_path          = $this->to_unix_dir_separator(resource_path('lang'));
        $directory_iterator = new \RecursiveDirectoryIterator($lang_path);
        $recursive_iterator = new \RecursiveIteratorIterator($directory_iterator);
        $regex              = '/^.+\\\\' . $this->base_locale . '\\\\.+\.php$/i';
        $regex_iterator     = new \RegexIterator($recursive_iterator, $regex, \RecursiveRegexIterator::GET_MATCH);

        $files = [];

        foreach ($regex_iterator as $file_info) {
            $files[] = $this->to_unix_dir_separator($file_info[0]);
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

    /**
     * Returns the formatted translation group name from a file, taking into account if it's a vendor language file
     * @param string $file
     * @return string
     */
    public function file_to_namespace(string $file): string
    {
        if (! Str::contains($file, '/vendor/')) {
            return basename(strtolower($file), '.php');
        }

        // It's a vendor file, so the group is like vendor/package::group
        $sub_vendor_path = Str::after($file, '/vendor/');
        $namespace       = Str::before($sub_vendor_path, '/' . $this->base_locale . '/');
        $group           = Str::after($sub_vendor_path, '/' . $this->base_locale . '/');

        $group = $namespace . '::' . basename(strtolower($group), '.php');

        // We make sure the namespace is added to the translator app
        Lang::addNamespace($namespace, $file);

        return $group;
    }

    public function to_unix_dir_separator(string $path): string
    {
        return str_replace('\\', '/', $path);
    }

    public function setTargetFiles($target_files)
    {
        $this->target_files = $target_files;
    }

    public function setExcludedFiles($excluded_files)
    {
        $this->excluded_files = $excluded_files;
    }
}
