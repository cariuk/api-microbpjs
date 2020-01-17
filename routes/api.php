<?php

use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/
Route::group(['namespace' => 'Api'], function() {
    Route::middleware('throttle:60,1')->post("token", "TokenController@getLogin");

    Route::group(['middleware' => 'auth:api'], function () {
        Route::group(['prefix' => 'antrian'], function () {
            Route::post('/nomor', 'AntrianController@setData');
            Route::post('/data', 'AntrianController@getData');
        });
    });
});

