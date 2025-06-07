<?php

require_once __DIR__.'/../vendor/autoload.php';

(new Laravel\Lumen\Bootstrap\LoadEnvironmentVariables(
    dirname(__DIR__)
))->bootstrap();

date_default_timezone_set(env('APP_TIMEZONE', 'UTC'));

$app = new Laravel\Lumen\Application(
    dirname(__DIR__)
);

$app->withFacades(); // Aktifkan facades untuk \Log

$app->singleton(
    Illuminate\Contracts\Debug\ExceptionHandler::class,
    App\Exceptions\Handler::class
);

$app->singleton(
    Illuminate\Contracts\Console\Kernel::class,
    App\Console\Kernel::class
);

// Konfigurasi logging
$app->configure('logging');
$app->register(Illuminate\Log\LogServiceProvider::class);

// Middleware CORS
$app->middleware([
    function ($request, $next) {
        if ($request->getMethod() === 'OPTIONS') {
            return response('', 200)
                ->header('Access-Control-Allow-Origin', '*')
                ->header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS')
                ->header('Access-Control-Allow-Headers', 'Content-Type, Authorization');
        }
        $response = $next($request);
        return $response->header('Access-Control-Allow-Origin', '*')
                       ->header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS')
                       ->header('Access-Control-Allow-Headers', 'Content-Type, Authorization');
    }
]);

$app->router->group([
    'namespace' => 'App\Http\Controllers',
], function ($router) {
    require __DIR__.'/../routes/web.php';
});

return $app;