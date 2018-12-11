<?php

namespace Tanmuhittin\LaravelGoogleTranslate\Commands;

use Illuminate\Console\Command;

class TranslateFilesCommand extends Command
{
    public $locales,$base_locale,$excluded_files;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'translate:files {--baselocale=en : Set the base locale. default is en} {--exclude=auth,pagination,validation,passwords : comma separated list of excluded files. default is auth,pagination,passwords,validation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Translate Translation files';

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
     * Translate from base language to other available languages
     *
     * @return mixed
     */
    public function handle()
    {
        $this->base_locale = $this->option('baselocale');
        $this->excluded_files = explode(",",$this->option('exclude'));
        $bar = $this->output->createProgressBar((count($this->locales)-1));
        $bar->start();
        foreach ($this->locales as $locale){
            if($locale == $this->base_locale)
                continue;
            $this->line($this->base_locale." -> ".$locale." translating...");
            $files = preg_grep('/^([^.])/', scandir(resource_path('lang/'.$this->base_locale)));
            foreach ($files as $file){
                $file = substr($file,0,-4);
                if(in_array($file,$this->excluded_files))
                    continue;
                $to_be_translateds = trans($file,[],$this->base_locale);
                $new_lang = [];
                foreach ($to_be_translateds as $key=>$to_be_translated){
                    $new_lang[$key]=addslashes(self::translate($to_be_translated,$locale));
                }
                //save new lang to new file
                $file = fopen(resource_path('lang/'.$locale.'/'.$file.'.php'), "w+");
                $write_text="<?php \nreturn " . var_export($new_lang, true) . ";";
                fwrite($file, $write_text);
                fclose($file);
            }
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
    private function translate($text,$locale){
        $apiKey = config('laravel_google_translate.google_translate_api_key');
        $url = 'https://www.googleapis.com/language/translate/v2?key=' . $apiKey . '&q=' . rawurlencode($text) . '&source='.$this->base_locale.'&target='.$locale;
        $handle = curl_init();
        curl_setopt($handle, CURLOPT_URL, $url);
        curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($handle);
        if ($response === false) {
            throw new \Exception(curl_error($handle), curl_errno($handle));
        }
        $responseDecoded = json_decode($response, true);
        curl_close($handle);

        if(isset($responseDecoded['error'])){
            echo $responseDecoded['error'][0]["message"]."\n";
            exit;
        }

        return $responseDecoded['data']['translations'][0]['translatedText'];
    }
}
