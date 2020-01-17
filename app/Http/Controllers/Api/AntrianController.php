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
            'notlp' => 'required',
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
                $new->ID = "";
        }
    }
}
