<?php
// File: Api-gateway/bootstrap/app.php

require_once __DIR__.'/../vendor/autoload.php';

(new Laravel\Lumen\Bootstrap\LoadEnvironmentVariables(
    dirname(__DIR__)
))->bootstrap();

date_default_timezone_set(env('APP_TIMEZONE', 'UTC'));

$app = new Laravel\Lumen\Application(
    dirname(__DIR__)
);

$app->withFacades(); 

$app->singleton(
    Illuminate\Contracts\Debug\ExceptionHandler::class,
    App\Exceptions\Handler::class
);

$app->singleton(
    Illuminate\Contracts\Console\Kernel::class,
    App\Console\Kernel::class
);

$app->configure('logging');
$app->register(Illuminate\Log\LogServiceProvider::class);

// Middleware CORS dan CSP
$app->middleware([
    function ($request, $next) {
        if ($request->getMethod() === 'OPTIONS') {
            return response('', 200)
                ->header('Access-Control-Allow-Origin', '*')
                ->header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS')
                ->header('Access-Control-Allow-Headers', 'Content-Type, Authorization');
        }

        $response = $next($request);

        // Aturan Content Security Policy (CSP) ditambahkan di sini
        $csp = "default-src 'self'; " .
               "script-src 'self' 'unsafe-eval' https://app.sandbox.midtrans.com https://cdn.tailwindcss.com; " .
               "style-src 'self' 'unsafe-inline' https://cdn.tailwindcss.com; " .
               "frame-src 'self' https://app.sandbox.midtrans.com; " .
               "connect-src 'self' http://localhost:8000; " . // Sesuaikan jika API gateway Anda di port berbeda
               "img-src 'self' data: *;"; // Izinkan gambar dari mana saja, termasuk data URL

        $response->header('Content-Security-Policy', $csp);
        $response->header('Access-Control-Allow-Origin', '*');
        $response->header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
        $response->header('Access-Control-Allow-Headers', 'Content-Type, Authorization');
        
        return $response;
    }
]);


$app->router->group([
    'namespace' => 'App\Http\Controllers',
], function ($router) {
    require __DIR__.'/../routes/web.php';
});

return $app;