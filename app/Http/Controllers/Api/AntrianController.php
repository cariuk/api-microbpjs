<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Model\AntrianOnlineModel;
use App\Model\MappingPoliAntrianModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class AntrianController extends Controller
{
    function setData(Request $request){
        $validator = Validator::make(
            $request->all(), [
            'nomorkartu' => 'required|min:13|max:13',
            'nik' => 'required|min:16|max:16',
            'nomorrm' => 'required',
            'notelp' => 'required',
            'tanggalperiksa' => 'required|date_format:Y-m-d|after:tomorrow|before:'.date("Y-m-d",strtotime("+8 day")),
            'kodepoli' => 'required',
            'nomorreferensi' => 'required',
            'jenisreferensi' => 'required|in:1,2',
            'jenisrequest' => 'required|in:1,2',
            'polieksekutif' => 'required|in:0,1'
        ],[]);


        if ($validator->fails()){
            return response()->json([
                "metadata" =>[
                    "status" => 422,
                    "message" => $validator->messages()->first()
                ]
            ],422);
        }

        /*Check Tanggal Pengambilan Antrian*/
        if ($request->tanggal==date("Y-m-d",strtotime("+1 day"))){
            if (strtotime(date('Y-m-d')." 18:00:00") < strtotime(date("Y-m-d H:i:s"))){
                return response()->json([
                    "metadata" =>[
                        "status" => 422,
                        "message" => "TESTING"
                    ]
                ],422);
            }
        }


        /*CheckMapping*/
        $mappingPoliantrian = MappingPoliAntrianModel::where([
            "KODE_POLI" => $request->kodepoli
        ])->first();

        if ($mappingPoliantrian==null){
            return response()->json([
                "metadata" =>[
                    "status" => 422,
                    "message" => " Kode Poli Tidak Sesuai"
                ]
            ],422);
        }

        if ((($mappingPoliantrian->KODE_ANTRIAN==null)||($mappingPoliantrian->KODE_ANTRIAN==""))){
            return response()->json([
                "metadata" =>[
                    "status" => 422,
                    "message" => " Kode Poli Belum Tersedia Antriannya"
                ]
            ],422);
        }

        /*Check Data Antrian*/
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
                $new->NOMOR = $mappingPoliantrian->KODE_ANTRIAN." ".rand(1,20);
                $new->TANGGAL = now();
            $new->save();

            /*Check Again*/
            $checkAntrian = AntrianOnlineModel::where([
                "NOMOR_KARTU" => $request->nomorkartu,
                "KODE_POLI" => $request->kodepoli,
                "TANGGAL_PERIKSA" => $request->tanggalperiksa
            ])->first();
        }

        return response()->json([
            "metadata" => [
                "status" => 200,
                "message" => "Ok"
            ],"response" =>[
                "nomorantrean" => $checkAntrian->NOMOR,
                "kodebooking" => $checkAntrian->ID,
                "jenisantrean" => $checkAntrian->JENIS_REFERENSI,
                "estimasidilayani" => strtotime($checkAntrian->TANGGAL_PERIKSA)*1000,
                "namapoli" => $mappingPoliantrian->NAMA_POLI,
                "namadokter" => "",
            ]
        ]);
    }

    function getRekap(Request $request){
        $validator = Validator::make(
            $request->all(), [
            'tanggalperiksa' => 'required|date_format:Y-m-d',
            'kodepoli' => 'required',
            'polieksekutif' => 'required|in:0,1',
        ],[]);

        if ($validator->fails()){
            return response()->json([
                "metadata" =>[
                    "status" => 422,
                    "message" => $validator->messages()->first()
                ]
            ],422);
        }
        /*CheckMapping*/
        $mappingPoliantrian = MappingPoliAntrianModel::where([
            "KODE_POLI" => $request->kodepoli,
        ])->first();

        if ($mappingPoliantrian==null){
            return response()->json([
                "metadata" =>[
                    "status" => 422,
                    "message" => " Kode Poli Tidak Sesuai"
                ]
            ],422);
        }

        if ((($mappingPoliantrian->KODE_ANTRIAN==null)||($mappingPoliantrian->KODE_ANTRIAN==""))){
            return response()->json([
                "metadata" =>[
                    "status" => 422,
                    "message" => "Kode Poli Belum Tersedia Antriannya"
                ]
            ],422);
        }

        return response()->json([
            "metadata" => [
                "status" => 200,
                "message" => "Ok"
            ],"response" =>[
                "namapoli" => $mappingPoliantrian->NAMA_POLI,
                "totalantrian" => 0, /*Ambil Dari Antrian Sirspro*/
                "jumlahterlayani" => 0, /*Ambil Dari Antrian Sirspro*/
                "lastupdate" => round(microtime(true) * 1000),
                "lastupdatetanggal" => date("Y-m-d H:m:i"),
            ]
        ]);
    }
}
