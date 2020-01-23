<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class JadwalOperasiController extends Controller{
    function getData(Request $request){
        $validator = Validator::make(
            $request->all(), [
            'nomorpeserta' => 'required|min:13|max:13',
        ],[]);

        if ($validator->fails()){
            return response()->json([
                "metadata" =>[
                    "code" => 422,
                    "message" => $validator->messages()->first()
                ]
            ],422);
        }

        $jadwalOperasi = JadwalOperasiController::select(
            "jadwal_operasi.*"
        )->join("pendaftaran.penjamin pp",function ($join){
            $join->on("pp.NOPEN","jadwal_operasi.NOPEN")->where(
                "pp.JENIS",2
            );
        })->where("jadwal_operasi.code",1)->first();

        if ($jadwalOperasi==null){
            return response()->json([
                "metadata" => [
                    "code" => 404,
                    "message" => "Tidak Ada Jadwal Operasi"
                ]
            ],404);
        }

        return response()->json([
            "metadata" => [
                "code" => 200,
                "message" => "Ok"
            ],"response" =>[
                "list" => []
            ]
        ]);
    }

    function getDataByTanggal(Request $request){
        $validator = Validator::make(
            $request->all(), [
            'tanggalawal' => 'required|date_format:Y-m-d',
            'tanggalakhir' => 'required|date_format:Y-m-d|after:'.date("Y-m-d",strtotime($request->tanggalawal)-1).'|before:'.date("Y-m-d",strtotime("+9 day")),

        ],[]);

        if ($validator->fails()){
            return response()->json([
                "metadata" =>[
                    "code" => 422,
                    "message" => $validator->messages()->first()
                ]
            ],422);
        }

        return response()->json([
            "metadata" => [
                "code" => 200,
                "message" => "Ok"
            ],"response" =>[
                "list" => []
            ]
        ]);
    }
}
