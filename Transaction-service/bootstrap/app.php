<?php

require_once __DIR__.'/../vendor/autoload.php';

(new Laravel\Lumen\Bootstrap\LoadEnvironmentVariables(
    dirname(__DIR__)
))->bootstrap();

date_default_timezone_set(env('APP_TIMEZONE', 'UTC'));

/*
|--------------------------------------------------------------------------
| Create The Application
|--------------------------------------------------------------------------
*/

$app = new Laravel\Lumen\Application(
    dirname(__DIR__)
);

$app->withFacades(); // Pastikan ini tidak dikomentari untuk menggunakan Facade seperti Log
$app->withEloquent(); // Pastikan ini tidak dikomentari untuk menggunakan Eloquent

// Register Console Kernel for Artisan commands
$app->singleton(
    Illuminate\Contracts\Console\Kernel::class,
    Laravel\Lumen\Console\Kernel::class
);

// Register Exception Handler for console and application
$app->singleton(
    Illuminate\Contracts\Debug\ExceptionHandler::class,
    Laravel\Lumen\Exceptions\Handler::class
);

/*
|--------------------------------------------------------------------------
| Register Config Files
|--------------------------------------------------------------------------
*/
$app->configure('app');
$app->configure('midtrans'); // Jika Anda punya config/midtrans.php

/*
|--------------------------------------------------------------------------
| Register Middleware
|--------------------------------------------------------------------------
*/
$app->middleware([
    // App\Http\Middleware\ExampleMiddleware::class // Contoh global middleware jika ada
]);

$app->routeMiddleware([
    'auth' => App\Http\Middleware\AuthMiddleware::class, // Daftarkan AuthMiddleware Anda
]);

/*
|--------------------------------------------------------------------------
| Register Service Providers
|--------------------------------------------------------------------------
*/
$app->register(Flipbox\LumenGenerator\LumenGeneratorServiceProvider::class);
// $app->register(App\Providers\AppServiceProvider::class);
// $app->register(App\Providers\AuthServiceProvider::class);
// $app->register(App\Providers\EventServiceProvider::class);

/*
|--------------------------------------------------------------------------
| Load The Application Routes
|--------------------------------------------------------------------------
*/
$app->router->group([
    'namespace' => 'App\Http\Controllers',
], function ($router) {
    require __DIR__.'/../routes/web.php';
});

return $app;