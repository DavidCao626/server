<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Encryption Key
    |--------------------------------------------------------------------------
    |
    | This key is used by the Illuminate encrypter service and should be set
    | to a random, 32 character string, otherwise these encrypted strings
    | will not be safe. Please do this before deploying an application!
    |
    */

    'key' => envOverload('APP_KEY', 'xlhF31NeOlibJcoOW9tvZg7TkHcAZI3a'),

    'cipher' => 'AES-256-CBC',

    /*
    |--------------------------------------------------------------------------
    | Application Locale Configuration
    |--------------------------------------------------------------------------
    |
    | The application locale determines the default locale that will be used
    | by the translation service provider. You are free to set this value
    | to any of the locales which will be supported by the application.
    |
    */
    'locale' => envOverload('APP_LOCALE', 'zh-CN'),
    /*
    |--------------------------------------------------------------------------
    | Application Fallback Locale
    |--------------------------------------------------------------------------
    |
    | The fallback locale determines the locale to use when the current one
    | is not available. You may change the value to correspond to any of
    | the language folders that are provided through your application.
    |
    */
    'fallback_locale' => envOverload('APP_FALLBACK_LOCALE', 'en'),

    //sql执行时间(ms)
    'long_query_time' => envOverload('LONG_QUERY_TIME', '1000'),

    //sql执行时间(ms)
    'api_execute_time' => envOverload('API_EXECUTE_TIME', '1000'),

    //url请求时间(s)
    'url_request_time' => envOverload('URL_REQUEST_TIME', '30'),

    //导入失败字体颜色
    'import_fail_color' => 'FF0000',

    //导入成功字体颜色
    'import_success_color' => '00CD00',

    //错误是否记录日志
    'custom_error' => envOverload('CUSTOM_ERROR', true),
];
