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
        Route::group(['prefix' => 'antrian','namespace' => 'Antrian'], function () {
            Route::post('/nomor', 'PengambilanNomorController@setData');

            Route::post('/status', 'InformasiController@getStatus');
            Route::post('/sisa', 'InformasiController@getSisaNomor');

            Route::post('/batal', 'PembatalanController@setData');
            Route::post('/cekin', 'CekInController@setData');
        });

        Route::group(['prefix' => 'pasien','namespace' => 'Pasien'], function () {
            Route::post('/baru', 'RegisterController@setData');
        });

        Route::group(['prefix' => 'jadwaloperasi','namespace' => 'JadwalOperasi'], function () {
            Route::post('/list', 'ListDataController@getData');
            Route::post('/list/tanggal', 'ListDataController@getDataByTanggal');
        });
    });
});

