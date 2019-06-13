<?php

namespace Tanmuhittin\LaravelGoogleTranslate\Commands;

use Illuminate\Console\Command;
use Stichoza\GoogleTranslate\GoogleTranslate;
use Symfony\Component\Finder\Finder;

class TranslateFilesCommand extends Command
{
    public $base_locale;
    public $locales;
    public $excluded_files;
    public $target_files;
    public $json;
    public $force;
    public $verbose;
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
     * @param string $base_locale
     * @param string $locales
     * @param bool $force
     * @param bool $json
     * @param string $target_files
     * @param string $excluded_files
     * @param bool $verbose
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
        $this->base_locale = $this->ask('What is base locale?',config('app.locale','en'));
        $this->locales = array_filter(explode(",", $this->ask('What are the target locales? Comma seperate each lang key','tr,it')));
        $should_force = $this->choice('Force overwrite existing translations?',['No','Yes'],'No');
        $this->force = false;
        if($should_force === 'Yes'){
            $this->force = true;
        }
        $mode = $this->choice('Use text exploration and json translation or php files?',['json','php'],'php');
        $this->json = false;
        if($mode === 'json'){
            $this->json = true;
        }
        if(!$this->json){
            $this->target_files = array_filter(explode(",", $this->ask('Are there specific target files to translate only? ex: file1,file2','')));
            foreach ($this->target_files as $key=>$target_file){
                $this->target_files[$key] = $target_file.'.php';
            }
            $this->excluded_files = array_filter(explode(",", $this->ask('Are there specific files to exclude?','auth,pagination,validation,passwords')));
        }
        $should_verbose = $this->choice('Verbose each translation?',['No','Yes'],'Yes');
        $this->verbose = false;
        if($should_verbose === 'Yes'){
            $this->verbose = true;
        }
        //Start Translating
        $bar = $this->output->createProgressBar(count($this->locales));
        $bar->start();
        $this->line("");
        // loop target locales
        if($this->json){
            $this->line("Exploring strings...");
            $stringKeys = $this->explore_strings();
            $this->line('Exploration completed. Let\'s get started');
        }
        foreach ($this->locales as $locale) {
            if ($locale == $this->base_locale) {
                continue;
            }
            $this->line($this->base_locale . " -> " . $locale . " translating...");
            if($this->json){
                $this->translate_json_array_file($locale,$stringKeys);
            }
            else if ($locale !== 'vendor') {
                if(!is_dir(resource_path('lang/' . $locale))){
                    mkdir(resource_path('lang/' . $locale));
                }
                $this->translate_php_array_files($locale);
            }
            $bar->advance();
            $this->line("");
        }
        $bar->finish();
        $this->line("");
        $this->line("Translations Completed.");
    }

    /**
     * @param $base_locale
     * @param $locale
     * @param $text
     * @return mixed|null|string
     * @throws \ErrorException
     * @throws \Exception
     */
    public static function translate($base_locale, $locale, $text)
    {
        preg_match_all("/(^:|([\s|\:])\:)([a-zA-z])+/",$text,$matches);
        $parameter_map = [];
        $i = 1;
        foreach($matches[0] as $match){
            $parameter_map ["x".$i]= $match;
            $text = str_replace($match," x".$i,$text);
            $i++;
        }
        if(config('laravel_google_translate.google_translate_api_key', false)){
            $translated = self::translate_via_api_key($base_locale, $locale, $text);
        }else{
            $translated = self::translate_via_stichoza($base_locale, $locale, $text);
        }
        foreach ($parameter_map as $key=>$attribute){
            $combinations = [
                $key,
                substr($key,0,1)." ".substr($key,1),
                strtoupper(substr($key,0,1))." ".substr($key,1),
                strtoupper(substr($key,0,1)).substr($key,1)
            ];
            foreach ($combinations as $combination){
                $translated = str_replace($combination,$attribute,$translated,$count);
                if($count > 0)
                    break;
            }
        }
        $translated = str_replace("  :"," :",$translated);
        return $translated;
    }

    /**
     * @param $base_locale
     * @param $locale
     * @param $text
     * @return null|string
     * @throws \ErrorException
     */
    private static function translate_via_stichoza($base_locale, $locale, $text){
        $tr = new GoogleTranslate();
        $tr->setSource($base_locale);
        $tr->setTarget($locale);
        return $tr->translate($text);
    }

    /**
     * @param $base_locale
     * @param $locale
     * @param $text
     * @return mixed
     * @throws \Exception
     */
    private static function translate_via_api_key($base_locale, $locale, $text){
        $apiKey = config('laravel_google_translate.google_translate_api_key', false);
        $url = 'https://www.googleapis.com/language/translate/v2?key=' . $apiKey . '&q=' . rawurlencode($text) . '&source=' . substr($base_locale, 0, strpos($base_locale."_", "_")) . '&target=' . substr($locale, 0, strpos($locale."_", "_"));
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
            /*$this->error("Google Translate API returned error");
            if (isset($responseDecoded["error"]["message"])) {
                $this->error($responseDecoded["error"]["message"]);
            }*/
            var_dump($responseDecoded);
            exit;
        }

        return $responseDecoded['data']['translations'][0]['translatedText'];
    }

    /**
     * @param $locale
     * @throws \Exception
     */
    public function translate_php_array_files($locale)
    {
        $files = preg_grep('/^([^.])/', scandir(resource_path('lang/' . $this->base_locale)));

        if (count($this->target_files) > 0) {
            $files = $this->target_files;
        }
        foreach ($files as $file) {
            $file = substr($file, 0, -4);
            $already_translateds = [];
            if (file_exists(resource_path('lang/' . $locale . '/' . $file . '.php'))) {
                if($this->verbose)
                    $this->line('File already exists: lang/' . $locale . '/' . $file . '.php. Checking missing translations');
                $already_translateds = trans($file, [], $locale);
            }
            if (in_array($file, $this->excluded_files)) {
                continue;
            }
            $to_be_translateds = trans($file, [], $this->base_locale);
            $new_lang = [];
            if(is_array($to_be_translateds)){
                foreach ($to_be_translateds as $key => $to_be_translated) {
                    if (isset($already_translateds[$key]) && $already_translateds[$key] != '' && !$this->force) {
                        $new_lang[$key] = $already_translateds[$key];
                        if ($this->verbose) {
                            $this->line('Exists Skipping -> ' . $to_be_translated . ' : ' . $new_lang[$key]);
                        }
                        continue;
                    }
                    $new_lang[$key] = $this->translate_attribute($to_be_translated,$locale);
                }
            }
            //save new lang to new file
            if(!file_exists(resource_path('lang/' . $locale ))){
                mkdir(resource_path('lang/' . $locale ));
            }
            $file = fopen(resource_path('lang/' . $locale . '/' . $file . '.php'), "w+");
            $write_text = "<?php \nreturn " . var_export($new_lang, true) . ";";
            fwrite($file, $write_text);
            fclose($file);
        }
        return;
    }

    private function translate_attribute($attribute,$locale){
        if(is_array($attribute)){
            $return = [];
            foreach ($attribute as $k => $t){
                $return[$k] = $this->translate_attribute($t,$locale);
            }
            return $return;
        }else{
            $translated = self::translate($this->base_locale, $locale, $attribute);
            if ($this->verbose) {
                $this->line($attribute . ' : ' . $translated);
            }
            return $translated;
        }
    }

    /**
     * @return array
     */
    public function explore_strings(){
        $groupKeys  = [];
        $stringKeys = [];
        $functions  = config('laravel_google_translate.trans_functions', [
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
                        if($this->verbose){
                            $this->line('Found : '.$key);
                        }
                    }
                }
            }
        }
        // Remove duplicates
        $groupKeys  = array_unique( $groupKeys ); // todo: not supporting group keys for now add this feature!
        $stringKeys = array_unique( $stringKeys );
        return $stringKeys;
    }

    /**
     * @param $locale
     * @param $stringKeys
     * @throws \ErrorException
     * @throws \Exception
     */
    public function translate_json_array_file($locale,$stringKeys)
    {
        $new_lang = [];
        $json_existing_translations = [];
        if(file_exists(resource_path('lang/' . $locale . '.json'))){
            $json_translations_string = file_get_contents(resource_path('lang/' . $locale . '.json'));
            $json_existing_translations = json_decode($json_translations_string, true);
        }
        foreach ($stringKeys as $to_be_translated){
            //check existing translations
            if(isset($json_existing_translations[$to_be_translated]) &&
                $json_existing_translations[$to_be_translated]!='' &&
                !$this->force)
            {
                $new_lang[$to_be_translated] = $json_existing_translations[$to_be_translated];
                if($this->verbose)
                    $this->line('Exists Skipping -> ' . $to_be_translated . ' : ' . $new_lang[$to_be_translated]);
                continue;
            }
            $new_lang[$to_be_translated] = addslashes(self::translate($this->base_locale, $locale, $to_be_translated));
            if ($this->verbose) {
                $this->line($to_be_translated . ' : ' . $new_lang[$to_be_translated]);
            }
        }
        $file = fopen(resource_path('lang/' . $locale . '.json'), "w+");
        $write_text = json_encode($new_lang, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        fwrite($file, $write_text);
        fclose($file);
    }
}
