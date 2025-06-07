<?php

require_once __DIR__.'/../vendor/autoload.php';

(new Laravel\Lumen\Bootstrap\LoadEnvironmentVariables(
    dirname(__DIR__)
))->bootstrap();

date_default_timezone_set(env('APP_TIMEZONE', 'UTC'));

$app = new Laravel\Lumen\Application(
    dirname(__DIR__)
);

$app->withFacades();
$app->withEloquent();

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

// Register middleware
$app->routeMiddleware([
    'auth' => App\Http\Middleware\AuthMiddleware::class,
]);

// Register service providers
$app->register(App\Providers\AppServiceProvider::class);

// Load routes
$app->router->group([
    'namespace' => 'App\Http\Controllers',
], function ($router) {
    require __DIR__.'/../routes/web.php';
});

return $app;