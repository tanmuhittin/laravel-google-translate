<?php
return [
    'google_translate_api_key'=>env('GOOGLE_TRANSLATE_API_KEY', null),
    'yandex_translate_api_key'=>env('YANDEX_TRANSLATE_API_KEY', null),
    'custom_api_translator' => env('CUSTOM_API_TRANSLATOR', null),
    'custom_api_translator_key' => env('CUSTOM_API_TRANSLATOR_KEY', null),
    'api_limit_settings'=>[
        'no_requests_per_batch' => env('NO_REQUESTS_PER_BATCH', 5),
        'sleep_time_between_batches' => env('SLEEP_TIME_BETWEEN_BATCHES', 1)
    ],
    'default_target_locales'=>'tr,it',
    'trans_functions' => [
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
    ],
];
