<?php

namespace Tanmuhittin\LaravelGoogleTranslate\Translators;


use Tanmuhittin\LaravelGoogleTranslate\Contracts\ApiTranslatorContract;

class ApiTranslateWithAttribute extends ApiTranslate
{

    private $parameter_map;

    public function __construct(ApiTranslatorContract $translator, $request_per_second, $sleep_for_sec)
    {
        parent::__construct($translator, $request_per_second, $sleep_for_sec);
    }

    /**
     * Holds the logic for replacing laravel translation attributes like :attribute
     * @param $base_locale
     * @param $locale
     * @param $text
     * @return mixed|string
     */
    public function translateWithAttributes($text, $locale, $base_locale = null) : string
    {
        $this->api_limit_check();

        $text = $this->pre_handle_parameters($text);

        $translated = $this->translator->translate($text, $locale, $base_locale);

        $translated = $this->post_handle_parameters($translated);

        return $translated;
    }


    private function find_parameters($text)
    {
        preg_match_all("/(^:|([\s|\:])\:)([a-zA-z])+/", $text, $matches);
        return $matches[0];
    }


    private function replace_parameters_with_placeholders($text, $parameters)
    {
        $parameter_map = [];
        $i = 1;
        foreach ($parameters as $match) {
            $parameter_map ["x" . $i] = $match;
            $text = str_replace($match, " x" . $i, $text);
            $i++;
        }
        return ['parameter_map' => $parameter_map, 'text' => $text];
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
    private function post_handle_parameters($text)
    {
        foreach ($this->parameter_map as $key => $attribute) {
            $combinations = [
                $key,
                substr($key, 0, 1) . " " . substr($key, 1),
                strtoupper(substr($key, 0, 1)) . " " . substr($key, 1),
                strtoupper(substr($key, 0, 1)) . substr($key, 1)
            ];
            foreach ($combinations as $combination) {
                $text = str_replace($combination, $attribute, $text, $count);
                if ($count > 0)
                    break;
            }
        }
        return str_replace("  :", " :", $text);
    }
}
