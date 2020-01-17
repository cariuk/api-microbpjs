<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Model\AntrianOnlineModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AntrianController extends Controller
{
    function setData(Request $request){
        $validator = Validator::make(
            $request->all(), [
            'nomorkartu' => 'required',
            'nik' => 'required',
            'nomorrm' => 'required',
            'notelp' => 'required',
            'tanggalperiksa' => 'required',
            'kodepoli' => 'required',
            'nomorreferensi' => 'required',
            'jenisreferensi' => 'required',
            'jenisrequest' => 'required',
            'polieksekutif' => 'required'
        ],[]);

        if ($validator->fails()){
            return response()->json([
                "metadata" =>[
                    "status" => 422,
                    "message" => $validator->messages()->first()
                ]
            ],422);
        }

        $checkAntrian = AntrianOnlineModel::where([
            "NOMOR_KARTU" => $request->nomorkartu,
            "KODE_POLI" => $request->kodepoli,
            "TANGGAL_PERIKSA" => $request->tanggalperiksa
        ])->first();

        if ($checkAntrian==null){
            $new = new AntrianOnlineModel();
                $new->ID = AntrianOnlineModel::generateNOMOR();
                $new->NOMOR_KARTU = $request->nomorkartu;
                $new->NIK = $request->nik;
                $new->NOMOR_RM = $request->nomorrm;
                $new->NO_TELP = $request->notelp;
                $new->TANGGAL_PERIKSA = $request->tanggalperiksa;
                $new->KODE_POLI = $request->kodepoli;
                $new->NOMOR_REFERENSI = $request->nomorreferensi;
                $new->JENIS_REFERENSI = $request->jenisreferensi;
                $new->JENIS_REQUEST = $request->jenisrequest;
                $new->POLI_EKSEKUTIF = $request->polieksekutif;
                $new->NOMOR = rand(1,20);
                $new->TANGGAL = now();
            $new->save();
            $checkAntrian = AntrianOnlineModel::where([
                "NOMOR_KARTU" => $request->nomorkartu,
                "KODE_POLI" => $request->kodepoli,
                "TANGGAL_PERIKSA" => $request->tanggalperiksa
            ])->first();
        }

        return response()->json([
            "metadata" => [
                "status" => 200,
                "message" => "OK"
            ],"response" =>[
                "nomorantrean" => $checkAntrian->NOMOR,
                "kodebooking" => $checkAntrian->ID,
                "jenisantrean" => $checkAntrian->JENIS_REFERENSI,
                "estimasidilayani" => strtotime($checkAntrian->TANGGAL_PERIKSA),
                "namapoli" => "",
                "namadokter" => "",
            ]
        ]);
    }
}
