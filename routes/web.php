<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/
if (env("PROXY_REVERSE",false)){
    $app_url = config("app.url");
    if (!empty($app_url)) {
        URL::forceRootUrl($app_url);
        $schema = explode(':', $app_url)[0];
        URL::forceScheme($schema);
    }
}

Route::get('/', function () {
    return response()->json([
        "metadata" => [
            "status" => 200,
            "message" => "Api Micro BPJS V 2.0"
        ]
    ]);
});
