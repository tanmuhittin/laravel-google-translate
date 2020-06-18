<?php

namespace Tanmuhittin\LaravelGoogleTranslate\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Finder\Finder;
use Tanmuhittin\LaravelGoogleTranslate\TranslatorContract;

class TranslateFilesCommand extends Command
{
    private $request_count = 0;
    private $request_per_sec = 5;
    private $sleep_for_sec = 1;

    public $base_locale;
    public $locales;
    public $excluded_files;
    public $target_files;
    public $json;
    public $force;
    public $verbose;

    private $parameter_map;

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
     * @param TranslatorContract $translator
     * @param string $base_locale
     * @param string $locales
     * @param string $target_files
     * @param bool $force
     * @param bool $json
     * @param bool $verbose
     * @param string $excluded_files
     */
    public function __construct(TranslatorContract $translator, $base_locale = 'en', $locales = 'tr,it', $target_files = '', $force = false, $json = false, $verbose = true, $excluded_files = 'auth,pagination,validation,passwords')
    {
        parent::__construct();
        $this->translator = $translator;
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
     * Check if the API request limit reached.
     */
    private function api_limit_check(){
        if( $this->request_count >= $this->request_per_sec ){
            sleep($this->sleep_for_sec);
            $this->request_count = 0; //Reset the $request_count
        }
        $this->request_count++; //Increase the request_count by 1
    }


    private function find_parameters($text){
        preg_match_all("/(^:|([\s|\:])\:)([a-zA-z])+/",$text,$matches);
        return $matches[0];
    }


    private function replace_parameters_with_placeholders($text,$parameters){
        $parameter_map = [];
        $i = 1;
        foreach($parameters as $match){
            $parameter_map ["x".$i]= $match;
            $text = str_replace($match," x".$i,$text);
            $i++;
        }
        return ['parameter_map'=>$parameter_map,'text'=>$text];
    }

    private function pre_handle_parameters($text)
    {
        $parameters = $this->find_parameters($text);
        $replaced_text_and_parameter_map = $this->replace_parameters_with_placeholders($text, $parameters);
        $this->parameter_map = $replaced_text_and_parameter_map['parameter_map'];
        return $replaced_text_and_parameter_map['text'];
    }

    /**
     * Put back parameters to translated text
     * @param $text
     * @return mixed
     */
    private function post_handle_parameters($text){
        foreach ($this->parameter_map as $key=>$attribute){
            $combinations = [
                $key,
                substr($key,0,1)." ".substr($key,1),
                strtoupper(substr($key,0,1))." ".substr($key,1),
                strtoupper(substr($key,0,1)).substr($key,1)
            ];
            foreach ($combinations as $combination){
                $text = str_replace($combination,$attribute,$text,$count);
                if($count > 0)
                    break;
            }
        }
        return str_replace("  :"," :",$text);
    }

    /**
     * Holds the logic for replacing laravel translate attributes like :attribute
     * @param $base_locale
     * @param $locale
     * @param $text
     * @param TranslatorContract $translator
     * @return mixed|string
     */
    public function translate($base_locale, $locale, $text)
    {
        $text = $this->pre_handle_parameters($text);

        $this->api_limit_check();

        $translated = $this->translator->translate($base_locale, $locale, $text);

        $translated = $this->post_handle_parameters($translated);

        return $translated;
    }



    /**
     * todo : NEEDS REFACTORING
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
                $new_lang = $this->skipMultidensional($to_be_translateds, $already_translateds, $locale);
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

    /**
     * todo : NEEDS REFACTORING
     * Walks array recursively to find strings already translated
     *
     * @author Maykon Facincani <facincani.maykon@gmail.com>
     *
     * @param array $to_be_translateds
     * @param array $already_translateds
     * @param String $locale
     *
     * @return array
     */
    private function skipMultidensional($to_be_translateds, $already_translateds, $locale){
        $data = [];
        foreach($to_be_translateds as $key => $to_be_translated){
            if ( is_array($to_be_translateds[$key]) ) {
                if( !isset($already_translateds[$key]) ) {
                    $already_translateds[$key] = [];
                }
                $data[$key] = $this->skipMultidensional($to_be_translateds[$key], $already_translateds[$key], $locale);
            } else {
                if ( isset($already_translateds[$key]) && $already_translateds[$key] != '' && !$this->force) {
                    $data[$key] = $already_translateds[$key];
                    if ($this->verbose) {
                        $this->line('Exists Skipping -> ' . $to_be_translated . ' : ' . $data[$key]);
                    }
                    continue;
                } else {
                    $data[$key] = $this->translate_attribute($to_be_translated,$locale);
                }
            }
        }
        return $data;
    }

    private function translate_attribute($attribute,$locale){
        if(is_array($attribute)){
            $return = [];
            foreach ($attribute as $k => $t){
                $return[$k] = $this->translate_attribute($t,$locale);
            }
            return $return;
        }else{
            $translated = $this->translate($this->base_locale, $locale, $attribute);
            if ($this->verbose) {
                $this->line($attribute . ' : ' . $translated);
            }
            return $translated;
        }
    }

    /**
     * copied from Barryvdh\TranslationManager\Manager findTranslations
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
     * todo : NEEDS REFACTORING
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
            $new_lang[$to_be_translated] = addslashes($this->translate($this->base_locale, $locale, $to_be_translated));
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
