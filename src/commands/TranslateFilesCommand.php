<?php

namespace Tanmuhittin\LaravelGoogleTranslate\Commands;

use Illuminate\Console\Command;
use Stichoza\GoogleTranslate\GoogleTranslate;
use Symfony\Component\Finder\Finder;

class TranslateFilesCommand extends Command
{
    public $locales;
    public $base_locale;
    public $excluded_files;
    public $target_files;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'translate:files {--baselocale=en : Set the base locale. default is en}
    {--exclude=auth,pagination,validation,passwords : comma separated list of excluded files. default is auth,pagination,passwords,validation}
    {--targetlocales= : comma separated list of target locales} {--force : Force to overwrite target locale files} 
    {--targetfiles= : target files}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Translate Translation files. translate:files {--baselocale=en : Set the base locale. default is en}
    {--exclude=auth,pagination,validation,passwords : comma separated list of excluded files. default is auth,pagination,passwords,validation}
    {--targetlocales=tr,de : comma separated list of target locales}
    {--verbose : Verbose each translation} 
    {--force : Force to overwrite target locale files}
    {--targetfiles=file1,file2 : Only translate specific files}';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        $this->locales = preg_grep('/^([^.])/', scandir(resource_path('lang')));
    }

    /**
     * @throws \Exception
     */
    public function handle()
    {
        $this->base_locale = $this->option('baselocale');
        $this->target_files = array_filter(explode(",", $this->option('targetfiles')));
        $this->excluded_files = explode(",", $this->option('exclude'));
        $target_locales = array_filter(explode(",", $this->option('targetlocales')));
        if (count($target_locales) > 0) {
            $this->locales = $target_locales;
        }
        $bar = $this->output->createProgressBar((count($this->locales) - 1));
        $bar->start();
        // loop target locales
        $this->line("");
        foreach ($this->locales as $locale) {
            if ($locale == $this->base_locale) {
                continue;
            }
            if (is_dir(resource_path('lang/' . $locale)) && $locale !== 'vendor') {
                $this->line($this->base_locale . " -> " . $locale . " translating...");
                $this->translate_php_array_files($locale);
            }
            $this->translate_json_array_file($locale);
            $bar->advance();
        }
        $bar->finish();
        $this->line("");
        $this->line("Translations Complete.");
    }

    /**
     * Translate given $text from base_locale to $locale
     * @param $text
     * @param $locale
     * @return mixed
     * @throws \Exception
     */
    private function translate($text, $locale)
    {
        if(config('laravel_google_translate.google_translate_api_key')){
            return self::translate_via_api_key($text, $locale);
        }else{
            return self::translate_via_stichoza($text, $locale);
        }
    }

    /**
     * @param $text
     * @param $locale
     * @return null|string
     * @throws \ErrorException
     */
    private function translate_via_stichoza($text,$locale){
        $tr = new GoogleTranslate();
        $tr->setSource($this->base_locale);
        $tr->setTarget($locale);
        return $tr->translate($text);
    }

    /**
     * @param $text
     * @param $locale
     * @return mixed
     * @throws \Exception
     */
    private function translate_via_api_key($text, $locale){
        $apiKey = config('laravel_google_translate.google_translate_api_key');
        $url = 'https://www.googleapis.com/language/translate/v2?key=' . $apiKey . '&q=' . rawurlencode($text) . '&source=' . $this->base_locale . '&target=' . $locale;
        $handle = curl_init();
        curl_setopt($handle, CURLOPT_URL, $url);
        curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($handle);
        if ($response === false) {
            throw new \Exception(curl_error($handle), curl_errno($handle));
        }
        $responseDecoded = json_decode($response, true);
        curl_close($handle);

        if (isset($responseDecoded['error'])) {
            $this->error("Google Translate API returned error");
            if (isset($responseDecoded["error"]["message"])) {
                $this->error($responseDecoded["error"]["message"]);
            }
            var_dump($responseDecoded);
            exit;
        }

        return $responseDecoded['data']['translations'][0]['translatedText'];
    }

    /**
     * @param $locale
     * @throws \Exception
     */
    private function translate_php_array_files($locale)
    {
        $files = preg_grep('/^([^.])/', scandir(resource_path('lang/' . $this->base_locale)));

        if (count($this->target_files) > 0) {
            $files = $this->target_files;
        }
        foreach ($files as $file) {
            $file = substr($file, 0, -4);
            $already_translateds = [];
            if (file_exists(resource_path('lang/' . $locale . '/' . $file . '.php'))) {
                $this->line('File already exists: lang/' . $locale . '/' . $file . '.php. Checking missing translations');
                $already_translateds = trans($file, [], $locale);
            }
            if (in_array($file, $this->excluded_files)) {
                continue;
            }
            $to_be_translateds = trans($file, [], $this->base_locale);
            $new_lang = [];
            foreach ($to_be_translateds as $key => $to_be_translated) {
                if (isset($already_translateds[$key]) && $already_translateds[$key] != '' && !$this->option('force')) {
                    $new_lang[$key] = $already_translateds[$key];
                    if ($this->option('verbose')) {
                        $this->line('Exists Skipping -> ' . $to_be_translated . ' : ' . $new_lang[$key]);
                    }
                    continue;
                }
                $new_lang[$key] = addslashes(self::translate($to_be_translated, $locale));
                if ($this->option('verbose')) {
                    $this->line($to_be_translated . ' : ' . $new_lang[$key]);
                }
            }
            //save new lang to new file
            $file = fopen(resource_path('lang/' . $locale . '/' . $file . '.php'), "w+");
            $write_text = "<?php \nreturn " . var_export($new_lang, true) . ";";
            fwrite($file, $write_text);
            fclose($file);
        }
        return;
    }

    /**
     * @param $locale
     * @throws \Exception
     */
    private function translate_json_array_file($locale)
    {
        $groupKeys  = [];
        $stringKeys = [];
        $functions  = [
            'trans',
            'trans_choice',
            'Lang::get',
            'Lang::choice',
            'Lang::trans',
            'Lang::transChoice',
            '@lang',
            '@choice',
            '__',
            '$trans.get',
        ];
        $groupPattern =                          // See https://regex101.com/r/WEJqdL/6
            "[^\w|>]" .                          // Must not have an alphanum or _ or > before real method
            '(' . implode( '|', $functions ) . ')' .  // Must start with one of the functions
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
            '(' . implode( '|', $functions ) . ')' .             // Must start with one of the functions
            "\(" .                                          // Match opening parenthesis
            "(?P<quote>['\"])" .                            // Match " or ' and store in {quote}
            "(?P<string>(?:\\\k{quote}|(?!\k{quote}).)*)" . // Match any string that can be {quote} escaped
            "\k{quote}" .                                   // Match " or ' previously matched
            "[\),]";                                       // Close parentheses or new parameter
        $finder = new Finder();
        $finder->in( base_path() )->exclude( 'storage' )->exclude( 'vendor' )->name( '*.php' )->name( '*.twig' )->name( '*.vue' )->files();
        /** @var \Symfony\Component\Finder\SplFileInfo $file */
        foreach ( $finder as $file ) {
            // Search the current file for the pattern
            if ( preg_match_all( "/$groupPattern/siU", $file->getContents(), $matches ) ) {
                // Get all matches
                foreach ( $matches[ 2 ] as $key ) {
                    $groupKeys[] = $key;
                }
            }
            if ( preg_match_all( "/$stringPattern/siU", $file->getContents(), $matches ) ) {
                foreach ( $matches[ 'string' ] as $key ) {
                    if ( preg_match( "/(^[a-zA-Z0-9_-]+([.][^\1)\ ]+)+$)/siU", $key, $groupMatches ) ) {
                        // group{.group}.key format, already in $groupKeys but also matched here
                        // do nothing, it has to be treated as a group
                        continue;
                    }
                    //TODO: This can probably be done in the regex, but I couldn't do it.
                    //skip keys which contain namespacing characters, unless they also contain a
                    //space, which makes it JSON.
                    if ( !( mb_strpos( $key, '::' ) !== FALSE && mb_strpos( $key, '.' ) !==  FALSE )
                        || mb_strpos( $key, ' ' ) !== FALSE ) {
                        $stringKeys[] = $key;
                    }
                }
            }
        }
        // Remove duplicates
        $groupKeys  = array_unique( $groupKeys );
        $stringKeys = array_unique( $stringKeys );
        // Add the translations to the database, if not existing.
        /*foreach ( $groupKeys as $key ) {
            // Split the group and item
            list( $group, $item ) = explode( '.', $key, 2 );
            $this->missingKey( '', $group, $item );
        }
        */
        $new_lang = [];
        $json_translations_string = file_get_contents(resource_path('lang/' . $locale . '.json'));
        $json_existing_translations = json_decode($json_translations_string, true);
        foreach ($stringKeys as $to_be_translated){
            //check existing translations
            if(isset($json_existing_translations[$to_be_translated]) &&
                $json_existing_translations[$to_be_translated]!='' &&
                !$this->options('force'))
            {
                $new_lang[$to_be_translated] = $json_existing_translations[$to_be_translated];
                $this->line('Exists Skipping -> ' . $to_be_translated . ' : ' . $new_lang[$to_be_translated]);
                continue;
            }
            $new_lang[$to_be_translated] = addslashes(self::translate($to_be_translated, $locale));
            if ($this->option('verbose')) {
                $this->line($to_be_translated . ' : ' . $new_lang[$key]);
            }
        }
        $file = fopen(resource_path('lang/' . $locale . '.json'), "w+");
        $write_text = json_encode($new_lang, JSON_UNESCAPED_UNICODE);
        fwrite($file, $write_text);
        fclose($file);
    }
}
