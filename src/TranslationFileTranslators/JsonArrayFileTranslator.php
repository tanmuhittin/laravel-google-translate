<?php

namespace Tanmuhittin\LaravelGoogleTranslate\TranslationFileTranslators;

use Illuminate\Support\Str;
use Symfony\Component\Finder\Finder;
use Tanmuhittin\LaravelGoogleTranslate\Contracts\FileTranslatorContract;
use Tanmuhittin\LaravelGoogleTranslate\Helpers\ConsoleHelper;

class JsonArrayFileTranslator implements FileTranslatorContract
{
    use ConsoleHelper;
    private $base_locale;
    private $verbose;
    private $force;

    public function __construct($base_locale, $verbose = true, $force = false)
    {
        $this->base_locale = $base_locale;
        $this->verbose = $verbose;
        $this->force = $force;
    }

    /**
     * todo : NEEDS REFACTORING
     * @param $locale
     * @param $stringKeys
     * @throws \ErrorException
     * @throws \Exception
     */
    public function handle($target_locale)
    {
        $stringKeys = $this->explore_strings();
        $new_lang = [];
        $json_existing_translations = [];
        if (file_exists(resource_path('lang/' . $target_locale . '.json'))) {
            $json_translations_string = file_get_contents(resource_path('lang/' . $target_locale . '.json'));
            $json_existing_translations = json_decode($json_translations_string, true);
        }
        foreach ($stringKeys as $to_be_translated) {
            //check existing translations
            if (isset($json_existing_translations[$to_be_translated]) &&
                $json_existing_translations[$to_be_translated] != '' &&
                !$this->force) {
                $new_lang[$to_be_translated] = $json_existing_translations[$to_be_translated];
                if ($this->verbose)
                    $this->line('Exists Skipping -> ' . $to_be_translated . ' : ' . $new_lang[$to_be_translated]);
                continue;
            }
            $new_lang[$to_be_translated] = addslashes(Str::apiTranslateWithAttributes($to_be_translated, $target_locale, $this->base_locale));
            if ($this->verbose) {
                $this->line($to_be_translated . ' : ' . $new_lang[$to_be_translated]);
            }
        }
        $file = fopen(resource_path('lang/' . $target_locale . '.json'), "w+");
        $write_text = json_encode($new_lang, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        fwrite($file, $write_text);
        fclose($file);
    }

    /**
     * copied from Barryvdh\TranslationManager\Manager findTranslations
     * @return array
     */
    private function explore_strings()
    {
        $groupKeys = [];
        $stringKeys = [];
        $functions = config('laravel_google_translate.trans_functions', [
            'trans',
            'trans_choice',
            'Lang::get',
            'Lang::choice',
            'Lang::trans',
            'Lang::transChoice',
            '@lang',
            '@choice',
            '__',
            '\$trans.get',
            '\$t'
        ]);
        $groupPattern =                          // See https://regex101.com/r/WEJqdL/6
            "[^\w|>]" .                          // Must not have an alphanum or _ or > before real method
            '(' . implode('|', $functions) . ')' .  // Must start with one of the functions
            "\(" .                               // Match opening parenthesis
            "[\'\"]" .                           // Match " or '
            '(' .                                // Start a new group to match:
            '[a-zA-Z0-9_-]+' .               // Must start with group
            "([.](?! )[^\1)]+)+" .             // Be followed by one or more items/keys
            ')' .                                // Close group
            "[\'\"]" .                           // Closing quote
            "[\),]";                            // Close parentheses or new parameter
        $stringPattern =
            "[^\w]" .                                     // Must not have an alphanum before real method
            '(' . implode('|', $functions) . ')' .             // Must start with one of the functions
            "\(" .                                          // Match opening parenthesis
            "(?P<quote>['\"])" .                            // Match " or ' and store in {quote}
            "(?P<string>(?:\\\k{quote}|(?!\k{quote}).)*)" . // Match any string that can be {quote} escaped
            "\k{quote}" .                                   // Match " or ' previously matched
            "[\),]";                                       // Close parentheses or new parameter
        $finder = new Finder();
        $finder->in(base_path())->exclude('storage')->exclude('vendor')->name('*.php')->name('*.twig')->name('*.vue')->files();
        /** @var \Symfony\Component\Finder\SplFileInfo $file */
        foreach ($finder as $file) {
            // Search the current file for the pattern
            if (preg_match_all("/$groupPattern/siU", $file->getContents(), $matches)) {
                // Get all matches
                foreach ($matches[2] as $key) {
                    $groupKeys[] = $key;
                }
            }
            if (preg_match_all("/$stringPattern/siU", $file->getContents(), $matches)) {
                foreach ($matches['string'] as $key) {
                    if (preg_match("/(^[a-zA-Z0-9_-]+([.][^\1)\ ]+)+$)/siU", $key, $groupMatches)) {
                        // group{.group}.key format, already in $groupKeys but also matched here
                        // do nothing, it has to be treated as a group
                        continue;
                    }
                    //TODO: This can probably be done in the regex, but I couldn't do it.
                    //skip keys which contain namespacing characters, unless they also contain a
                    //space, which makes it JSON.
                    if (!(mb_strpos($key, '::') !== FALSE && mb_strpos($key, '.') !== FALSE)
                        || mb_strpos($key, ' ') !== FALSE) {
                        $stringKeys[] = $key;
                        if ($this->verbose) {
                            $this->line('Found : ' . $key);
                        }
                    }
                }
            }
        }
        // Remove duplicates
        $groupKeys = array_unique($groupKeys); // todo: not supporting group keys for now add this feature!
        $stringKeys = array_unique($stringKeys);
        return $stringKeys;
    }
}