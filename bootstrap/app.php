<?php

require_once __DIR__.'/../vendor/autoload.php';
require_once __DIR__.'/../config/constants.php';
setEnv('APP_TIMEZONE', date_default_timezone_get());
(new Laravel\Lumen\Bootstrap\LoadEnvironmentVariables(
    dirname(__DIR__)
))->bootstrap();

/*
|--------------------------------------------------------------------------
| Create The Application
|--------------------------------------------------------------------------
|
| Here we will load the environment and create the application instance
| that serves as the central piece of this framework. We'll use this
| application as an "IoC" container and router for this framework.
|
*/
$app = new Laravel\Lumen\Application(
    realpath(__DIR__.'/../')
);

/*
|--------------------------------------------------------------------------
| 加载用户配置文件
|--------------------------------------------------------------------------
|
| 加载配置文件，只需将您要加载的配置文件名称添加到以下数组中即可
| lumen框架追求性能，所以不建议添加过多的配置文件。
|
*/
$userConfigs = [
    'cache', 'auth', 'app', 'eoffice', 'export','import', 'webhook', 'module','dataFilter','elastic',
    'flowoutsend', 'report', 'customfields', 'lang', 'attachment','logging', 'rolecontrolfields',
    'extra_export', 'integrationcenter','weChat', 'project' ,'elasticLog'
];
foreach ($userConfigs as $config) {
    $app->configure($config);
}

/*
|--------------------------------------------------------------------------
| 注册别名
|--------------------------------------------------------------------------
|
| 注册别名只需将类和对应的别名添加到下面的数组中即可。
| lumen框架追求性能，所以不建议注册别名。
|
*/
$userAliases = [
    'Illuminate\Support\Facades\Request' => 'Request',
    'Illuminate\Support\Facades\Lang' => 'Lang',
    'App\Facades\Eoffice' => 'Eoffice',
    'SimpleSoftwareIO\QrCode\Facades\QrCode' => 'QrCode',
    'Illuminate\Support\Facades\Artisan' => 'Artisan',
    'Illuminate\Support\Facades\Crypt' => 'Crypt',
    'Mavinoo\Batch\BatchFacade' => 'Batch',
];
$app->withFacades(true, $userAliases);

$app->withEloquent();
/*
|--------------------------------------------------------------------------
| Register Container Bindings
|--------------------------------------------------------------------------
|
| Now we will register a few bindings in the service container. We will
| register the exception handler and the console kernel. You may add
| your own bindings here if you like or you can make another file.
|
*/
$app->singleton(
    Illuminate\Contracts\Debug\ExceptionHandler::class,
    App\Exceptions\Handler::class
);

$app->singleton(
    Illuminate\Contracts\Console\Kernel::class,
    App\Console\Kernel::class
);

/*
|--------------------------------------------------------------------------
| Register Middleware
|--------------------------------------------------------------------------
|
| Next, we will register the middleware with the application. These can
| be global middleware that run before and after each request into a
| route or middleware that'll be assigned to some specific routes.
|
*/
$app->routeMiddleware([
    'decodeParams' => \App\Http\Middleware\DecodeParams::class,
    'authCheck' => \App\Http\Middleware\AuthCheck::class,
    'menuPower' => \App\Http\Middleware\MenuPower::class,
    'ModulePermissionsCheck' => \App\Http\Middleware\ModulePermissionsCheck::class,
    'openApiMiddleware' => \App\Http\Middleware\OpenApiMiddleware::class,
    'AccessUnifiedMessage' => \App\Http\Middleware\AccessUnifiedMessage::class,
    'syncWorkWeChat' => \App\Http\Middleware\SyncWorkWeChat::class,
    'verifyCsrfReferer' => \App\Http\Middleware\VerifyCsrfReferer::class,
]);
if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] != 'GET') {
    $app->middleware([
        App\Http\Middleware\WebhookMiddleware::class
    ]);
}
/*
|--------------------------------------------------------------------------
| Register Service Providers
|--------------------------------------------------------------------------
|
| Here we will register all of the application's service providers which
| are used to bind services into the container. Service providers are
| totally optional, so you are not required to uncomment this line.
|
*/
if (envOverload("SQL_LOG") === 'true') {
    $app->register(App\Providers\AppServiceProvider::class);
}
if (envOverload("ORACLE_ENABLE") == 1) {
    $app->register(Yajra\Oci8\Oci8ServiceProvider::class);
}

$app->register(SimpleSoftwareIO\QrCode\QrCodeServiceProvider::class);
$app->register(Illuminate\Redis\RedisServiceProvider::class);
$app->register(Milon\Barcode\BarcodeServiceProvider::class);
$app->register(Mavinoo\Batch\BatchServiceProvider::class);
/*
|--------------------------------------------------------------------------
| Load The Application Routes
|--------------------------------------------------------------------------
|
| Next we will include the routes file so that they can all be added to
| the application. This will provide all of the URLs the application
| can respond to, as well as the controllers that may handle them.
|
*/

$app->router->group([], function ($router) {
    require __DIR__.'/../routes/web.php';
});
$app->boot();
return $app;
