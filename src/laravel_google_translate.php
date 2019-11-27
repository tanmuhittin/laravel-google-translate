<?php
return [
    'google_translate_api_key'=>null,
     'yandex_translate_api_key'=>null,
    'translator' => 'yandex',
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
