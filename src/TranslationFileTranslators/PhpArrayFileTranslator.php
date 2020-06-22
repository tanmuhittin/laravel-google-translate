<?php

namespace Tanmuhittin\LaravelGoogleTranslate\TranslationFileTranslators;

use Illuminate\Support\Str;
use Tanmuhittin\LaravelGoogleTranslate\Contracts\FileTranslatorContract;
use Tanmuhittin\LaravelGoogleTranslate\Helpers\ConsoleHelper;

class PhpArrayFileTranslator implements FileTranslatorContract
{
    use ConsoleHelper;
    private $base_locale;
    private $target_files;
    private $excluded_files;
    private $verbose;

    public function __construct($base_locale, $verbose = true, $force = false)
    {
        $this->base_locale = $base_locale;
        $this->verbose = $verbose;
        $this->force = $force;
    }

    /**
     * todo : NEEDS REFACTORING
     * @param $target_locale
     * @throws \Exception
     */
    public function handle($target_locale)
    {
        if ($target_locale !== 'vendor') {
            if (!is_dir(resource_path('lang/' . $target_locale))) {
                mkdir(resource_path('lang/' . $target_locale));
            }
        }
        $files = preg_grep('/^([^.])/', scandir(resource_path('lang/' . $this->base_locale)));

        if (count($this->target_files) > 0) {
            $files = $this->target_files;
        }
        foreach ($files as $file) {
            $file = substr($file, 0, -4);
            $already_translateds = [];
            if (file_exists(resource_path('lang/' . $target_locale . '/' . $file . '.php'))) {
                if ($this->verbose)
                    $this->line('File already exists: lang/' . $target_locale . '/' . $file . '.php. Checking missing translations');
                $already_translateds = trans($file, [], $target_locale);
            }
            if (in_array($file, $this->excluded_files)) {
                continue;
            }
            $to_be_translateds = trans($file, [], $this->base_locale);
            $new_lang = [];
            if (is_array($to_be_translateds)) {
                $new_lang = $this->skipMultidensional($to_be_translateds, $already_translateds, $target_locale);
            }
            //save new lang to new file
            if (!file_exists(resource_path('lang/' . $target_locale))) {
                mkdir(resource_path('lang/' . $target_locale));
            }
            $file = fopen(resource_path('lang/' . $target_locale . '/' . $file . '.php'), "w+");
            $write_text = "<?php \nreturn " . var_export($new_lang, true) . ";";
            fwrite($file, $write_text);
            fclose($file);
        }
        return;
    }

    /**
     * todo : NEEDS REFACTORING
     * Walks array recursively to find strings already translated
     *
     * @author Maykon Facincani <facincani.maykon@gmail.com>
     *
     * @param array $to_be_translateds
     * @param array $already_translateds
     * @param String $target_locale
     *
     * @return array
     */
    private function skipMultidensional($to_be_translateds, $already_translateds, $target_locale)
    {
        $data = [];
        foreach ($to_be_translateds as $key => $to_be_translated) {
            if (is_array($to_be_translateds[$key])) {
                if (!isset($already_translateds[$key])) {
                    $already_translateds[$key] = [];
                }
                $data[$key] = $this->skipMultidensional($to_be_translateds[$key], $already_translateds[$key], $target_locale);
            } else {
                if (isset($already_translateds[$key]) && $already_translateds[$key] != '' && !$this->force) {
                    $data[$key] = $already_translateds[$key];
                    if ($this->verbose) {
                        $this->line('Exists Skipping -> ' . $to_be_translated . ' : ' . $data[$key]);
                    }
                    continue;
                } else {
                    $data[$key] = $this->translate_attribute($to_be_translated, $target_locale);
                }
            }
        }
        return $data;
    }

    private function translate_attribute($attribute, $target_locale)
    {
        if (is_array($attribute)) {
            $return = [];
            foreach ($attribute as $k => $t) {
                $return[$k] = $this->translate_attribute($t, $target_locale);
            }
            return $return;
        } else {
            $translated = Str::apiTranslateWithAttributes($attribute, $target_locale, $this->base_locale);
            if ($this->verbose) {
                $this->line($attribute . ' : ' . $translated);
            }
            return $translated;
        }
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
