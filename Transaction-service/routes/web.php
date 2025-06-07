<?php

/** @var \Laravel\Lumen\Routing\Router $router */

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/

$router->get('/', function () use ($router) {
    return $router->app->version() . ' (Transaksi Service)';
});

// Group untuk API Transaksi yang dilindungi JWT
$router->group(['prefix' => 'api/transaksi', 'middleware' => 'auth'], function () use ($router) {
    $router->post('beli', 'TransaksiController@beli');
    $router->get('riwayat', 'TransaksiController@riwayat');
});

// Endpoint ini dipanggil oleh Midtrans, JANGAN dilindungi middleware 'auth'
$router->post('api/transaksi/notification', 'TransaksiController@handleNotification');