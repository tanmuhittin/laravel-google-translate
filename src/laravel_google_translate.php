<?php
return [
    'google_translate_api_key'=>null,
    'yandex_translate_api_key'=>null,
    'custom_api_translator' => null,
    'custom_api_translator_key' => null,
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
