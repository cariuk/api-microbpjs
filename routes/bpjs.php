<?php

/*
|--------------------------------------------------------------------------
| BPJS Web Service Routes
|--------------------------------------------------------------------------
|
| Endpoint BPJS format baru tanpa prefix 'api/'
| Format: /webservice/registrasionline/bpjs/*
| Endpoint lama tetap berfungsi di /api/*
|
*/

// Endpoint BPJS format baru (tanpa prefix api/)
Route::group(['prefix' => 'webservice/registrasionline/bpjs', 'namespace' => 'Api'], function() {
    // Get Token (GET)
    Route::middleware('throttle:60,1')->get("getToken", "BpjsRegistrationController@getToken");

    Route::group(['middleware' => 'auth:api'], function () {
        // Antrian endpoints
        Route::post('/createAntrian', 'BpjsRegistrationController@createAntrian');
        Route::post('/getStatusAntrian', 'BpjsRegistrationController@getStatusAntrian');
        Route::post('/getSisaAntrian', 'BpjsRegistrationController@getSisaAntrian');
        Route::post('/setBatalAntrian', 'BpjsRegistrationController@setBatalAntrian');
        Route::post('/checkInAntrian', 'BpjsRegistrationController@checkInAntrian');

        // Pasien endpoints
        Route::post('/createPasien', 'BpjsRegistrationController@createPasien');

        // Jadwal Operasi endpoints
        Route::post('/getJadwalOperasPasien', 'BpjsRegistrationController@getJadwalOperasPasien');
        Route::post('/getJadwalOperasiRs', 'BpjsRegistrationController@getJadwalOperasiRs');

        // Farmasi endpoints (belum diimplementasikan)
        Route::post('/antreanFarmasi', 'BpjsRegistrationController@antreanFarmasi');
        Route::post('/statusAntreanFarmasi', 'BpjsRegistrationController@statusAntreanFarmasi');
    });
});