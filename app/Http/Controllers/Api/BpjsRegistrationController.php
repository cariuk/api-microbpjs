<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\Antrian\PengambilanNomorController;
use App\Http\Controllers\Api\Antrian\InformasiController;
use App\Http\Controllers\Api\Antrian\CekInController;
use App\Http\Controllers\Api\Antrian\PembatalanNomorController;
use App\Http\Controllers\Api\Pasien\RegisterController;
use App\Http\Controllers\Api\JadwalOperasi\ListDataController;
use Illuminate\Http\Request;

/**
 * Controller wrapper untuk endpoint BPJS format baru
 * Endpoint format: /webservice/registrasionline/bpjs/*
 * Tetap menggunakan logic dari controller yang sudah ada
 */
class BpjsRegistrationController extends Controller
{
    /**
     * getToken - Mendapatkan token autentikasi
     * Endpoint: GET /webservice/registrasionline/bpjs/getToken
     * Mapping ke: TokenController@getLogin
     */
    public function getToken(Request $request)
    {
        $tokenController = new TokenController();
        return $tokenController->getLogin($request);
    }

    /**
     * createAntrian - Membuat antrian baru
     * Endpoint: POST /webservice/registrasionline/bpjs/createAntrian
     * Mapping ke: PengambilanNomorController@setData
     */
    public function createAntrian(Request $request)
    {
        $controller = new PengambilanNomorController();
        return $controller->setData($request);
    }

    /**
     * getStatusAntrian - Mendapatkan status antrian
     * Endpoint: POST /webservice/registrasionline/bpjs/getStatusAntrian
     * Mapping ke: InformasiController@getStatus
     */
    public function getStatusAntrian(Request $request)
    {
        $controller = new InformasiController();
        return $controller->getStatus($request);
    }

    /**
     * getSisaAntrian - Mendapatkan sisa antrian
     * Endpoint: POST /webservice/registrasionline/bpjs/getSisaAntrian
     * Mapping ke: InformasiController@getSisaNomor
     */
    public function getSisaAntrian(Request $request)
    {
        $controller = new InformasiController();
        return $controller->getSisaNomor($request);
    }

    /**
     * setBatalAntrian - Membatalkan antrian
     * Endpoint: POST /webservice/registrasionline/bpjs/setBatalAntrian
     * Mapping ke: PembatalanNomorController@setData
     */
    public function setBatalAntrian(Request $request)
    {
        $controller = new PembatalanNomorController();
        return $controller->setData($request);
    }

    /**
     * checkInAntrian - Check in antrian
     * Endpoint: POST /webservice/registrasionline/bpjs/checkInAntrian
     * Mapping ke: CekInController@setData
     */
    public function checkInAntrian(Request $request)
    {
        $controller = new CekInController();
        return $controller->setData($request);
    }

    /**
     * createPasien - Membuat pasien baru
     * Endpoint: POST /webservice/registrasionline/bpjs/createPasien
     * Mapping ke: RegisterController@setData
     */
    public function createPasien(Request $request)
    {
        $controller = new RegisterController();
        return $controller->setData($request);
    }

    /**
     * getJadwalOperasPasien - Mendapatkan jadwal operasi pasien
     * Endpoint: POST /webservice/registrasionline/bpjs/getJadwalOperasPasien
     * Mapping ke: ListDataController@getData
     */
    public function getJadwalOperasPasien(Request $request)
    {
        $controller = new ListDataController();
        return $controller->getData($request);
    }

    /**
     * getJadwalOperasiRs - Mendapatkan jadwal operasi RS berdasarkan tanggal
     * Endpoint: POST /webservice/registrasionline/bpjs/getJadwalOperasiRs
     * Mapping ke: ListDataController@getDataByTanggal
     */
    public function getJadwalOperasiRs(Request $request)
    {
        $controller = new ListDataController();
        return $controller->getDataByTanggal($request);
    }

    /**
     * antreanFarmasi - Membuat antrian farmasi
     * Endpoint: POST /webservice/registrasionline/bpjs/antreanFarmasi
     * TODO: Implementasi jika diperlukan
     */
    public function antreanFarmasi(Request $request)
    {
        return response()->json([
            "metadata" => [
                "code" => 501,
                "message" => "Endpoint belum diimplementasikan"
            ]
        ], 501);
    }

    /**
     * statusAntreanFarmasi - Mendapatkan status antrian farmasi
     * Endpoint: POST /webservice/registrasionline/bpjs/statusAntreanFarmasi
     * TODO: Implementasi jika diperlukan
     */
    public function statusAntreanFarmasi(Request $request)
    {
        return response()->json([
            "metadata" => [
                "code" => 501,
                "message" => "Endpoint belum diimplementasikan"
            ]
        ], 501);
    }
}