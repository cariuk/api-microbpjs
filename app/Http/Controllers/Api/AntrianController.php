<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Model\AntrianOnlineModel;
use App\Model\MappingPoliAntrianModel;
use App\Model\MappingPoliModel;
use App\ModelBridge\Cetakan\AntrianRJModel;
use App\ModelBridge\Cetakan\AntrianRJSMFModel;
use App\ModelBridge\Master\JenisLoketModel;
use App\ModelBridge\Pendaftaran\AntrianLoketModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Mockery\Exception;

class AntrianController extends Controller
{
    function setData(Request $request){
        $validator = Validator::make(
            $request->all(), [
            'nomorkartu' => 'required|min:13|max:13',
            'nik' => 'required|min:16|max:16',
            'notelp' => 'required',
            'tanggalperiksa' => 'required|date_format:Y-m-d|after:today|before:'.date("Y-m-d",strtotime("+8 day")),
            'kodepoli' => 'required',
            'nomorreferensi' => 'required',
            'jenisreferensi' => 'required|in:1,2',
            'jenisrequest' => 'required|in:1,2'
        ],[
            "nomorkartu.required" => "Nomor Kartu Peserta Harus Terisi",
            "nomorkartu.min" => "Nomor Kartu Minimal 13 Digit",
            "nomorkartu.max" => "Nomor Kartu Maximal 13 Digit",
            "nik.required" => "Nomor Induk Kependudukan Harus Terisi",
            "nik.min" => "Nomor Induk Kependudukan Minimal 16 Digit",
            "nik.max" => "Nomor Induk Kependudukan Maximal 16 Digit",
            "tanggalperiksa.required" => "Tanggal Periksa Harus Terisi",
            "tanggalperiksa.date_format" => "Format Tanggal Periksa Harus Sesuai Format",
            "tanggalperiksa.after" => "Tanggal Periksa Hanya Boleh Dipilih H +1 Sampai H +7 Dari Tanggal ".date("Y-m-d"),
            "tanggalperiksa.before" => "Tanggal Periksa Hanya Boleh Dipilih H +1 Sampai H +7".date("Y-m-d"),
            "kodepoli.before" => "Kode Poli Harus Terisi",
            "nomorreferensi.required" => "Nomor Referensi / Nomor Rujukan Harus Terisi",
            "jenisreferensi.required" => "Jenis Referensi Harus Terisi",
            "jenisreferensi.in" => "Jenis Referensi Hanya Boleh 1 = Nomor Rujukan | 2 = Nomor Kontrol ",
            "jenisrequest.required" => "Jenis Request Harus Terisi",
            "jenisrequest.in" => "Jenis Request Hanya Boleh 1 = Pendaftaran | 2 = Poli",
        ]);


        if ($validator->fails()){
            return response()->json([
                "metadata" =>[
                    "code" => 422,
                    "message" => $validator->messages()->first()
                ]
            ],422);
        }

        /*Check Tanggal Pengambilan Antrian*/
        if ($request->tanggal==date("Y-m-d",strtotime("+1 day"))){
            if (strtotime(date('Y-m-d')." 18:00:00") < strtotime(date("Y-m-d H:i:s"))){
                return response()->json([
                    "metadata" =>[
                        "code" => 422,
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
                    "code" => 422,
                    "message" => " Kode Poli Tidak Sesuai"
                ]
            ],422);
        }

        if ((($mappingPoliantrian->KODE_ANTRIAN==null)||($mappingPoliantrian->KODE_ANTRIAN==""))){
            return response()->json([
                "metadata" =>[
                    "code" => 422,
                    "message" => " Kode Poli Belum Tersedia Antriannya"
                ]
            ],422);
        }

        /*Get Mapping SMF*/
        $mappingPoli = MappingPoliModel::where([
            "KODE" => $mappingPoliantrian->KODE_POLI
        ])->first();

        /*Check Data Antrian*/
        $checkAntrian = AntrianOnlineModel::where([
            "NOMOR_KARTU" => $request->nomorkartu,
            "KODE_POLI" => $request->kodepoli,
            "NOMOR_REFERENSI" => $request->nomorreferensi,
            "TANGGAL_PERIKSA" => $request->tanggalperiksa
        ])->first();

        if ($checkAntrian==null){
            /*Get Nomor Antrian*/
            $new = new AntrianRJModel();
                $new->TANGGAL = $request->tanggalperiksa;
                $new->TIPE = $mappingPoliantrian->KODE_ANTRIAN;
                $new->WAKTU = now();
            $new->save();

            $smf = new AntrianRJSMFModel();
                $smf->TANGGAL = $new->TANGGAL;
                $smf->TIPE = $new->TIPE;
                $smf->KARCIS_RJ = $new->ID;
                $smf->BPJS = 1;
                $smf->SMF = $mappingPoli->SMF;
            $smf->save();

            $newAntrianOnline = new AntrianOnlineModel();
                $newAntrianOnline->ID = AntrianOnlineModel::generateNOMOR();
                $newAntrianOnline->NOMOR_KARTU = $request->nomorkartu;
                $newAntrianOnline->NIK = $request->nik;
                $newAntrianOnline->NOMOR_RM =  $request->nomorrm==null?0:$request->nomorrm;
                $newAntrianOnline->NO_TELP = $request->notelp;
                $newAntrianOnline->TANGGAL_PERIKSA = $request->tanggalperiksa;
                $newAntrianOnline->KODE_POLI = $request->kodepoli;
                $newAntrianOnline->NOMOR_REFERENSI = $request->nomorreferensi;
                $newAntrianOnline->JENIS_REFERENSI = $request->jenisreferensi;
                $newAntrianOnline->JENIS_REQUEST = $request->jenisrequest;
                $newAntrianOnline->POLI_EKSEKUTIF = $request->polieksekutif==null?0:$request->polieksekutif;
                $newAntrianOnline->NOMOR = $mappingPoliantrian->KODE_ANTRIAN." ".$new->ID;
                $newAntrianOnline->TANGGAL = now();
            $newAntrianOnline->save();

            /*Check Again*/
            $checkAntrian = AntrianOnlineModel::where([
                "NOMOR_KARTU" => $request->nomorkartu,
                "KODE_POLI" => $request->kodepoli,
                "TANGGAL_PERIKSA" => $request->tanggalperiksa
            ])->first();

            return response()->json([
                "metadata" => [
                    "code" => 200,
                    "message" => "Berhasil Mengambil Nomor Antrian"
                ],"response" =>[
                    "nomorantrean" => $checkAntrian->NOMOR,
                    "kodebooking" => $checkAntrian->ID,
                    "jenisantrean" => $checkAntrian->JENIS_REFERENSI,
                    "estimasidilayani" => strtotime($checkAntrian->TANGGAL_PERIKSA)*1000,
                    "namapoli" => $mappingPoliantrian->NAMA_POLI,
                    "namadokter" => "",
                ]
            ]);
        }else{
            return response()->json([
                "metadata" =>[
                    "code" => 422,
                    "message" => "Maaf Nomor Rujukan / Nomor Referensi Ini Telah Terbit Nomor Antriannya Di Tanggal "
                        .$checkAntrian->TANGGAL_PERIKSA." Nomor Urut : ".$checkAntrian->NOMOR
                ]
            ],422);
        }


    }

    function getRekap(Request $request){
        $validator = Validator::make(
            $request->all(), [
            'tanggalperiksa' => 'required|date_format:Y-m-d|before:'.date("Y-m-d",strtotime("+8 day")),
            'kodepoli' => 'required',
        ],[
            "tanggalperiksa.required" => 'Tanggal Periksa Harus Terisi',
            "tanggalperiksa.date_format" => 'Tanggal Periksa Harus Sesuai Dengan Format Y-m-d',
            "kodepoli.required" => 'Kode Poli Harus Terisi',
        ]);

        if ($validator->fails()){
            return response()->json([
                "metadata" =>[
                    "code" => 422,
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
                    "code" => 422,
                    "message" => " Kode Poli Tidak Sesuai"
                ]
            ],422);
        }

        if ((($mappingPoliantrian->KODE_ANTRIAN==null)||($mappingPoliantrian->KODE_ANTRIAN==""))){
            return response()->json([
                "metadata" =>[
                    "code" => 422,
                    "message" => "Kode Poli Belum Tersedia Antriannya"
                ]
            ],422);
        }
        try{
            /*Antrian Yang Terpanggil*/
            $jenis = JenisLoketModel::where([
                "HURUF" => $mappingPoliantrian->KODE_ANTRIAN
            ])->first();

            if ($jenis==null){
                return response()->json([
                    "metadata" =>[
                        "code" => 422,
                        "message" => "Kode Poli Belum Tersedia Antriannya"
                    ]
                ],422);
            }

            $terpanggil = AntrianLoketModel::select(
                "*"
            )->where([
                "JENIS" => $jenis->ID,
                "TANGGAL" => $request->tanggalperiksa
            ])->orderBy("WAKTU","DESC")->first();

            $antrian = AntrianRJModel::where([
                "TANGGAL" => date("Y-m-d",strtotime($request->tanggalperiksa)),
                "TIPE" => $mappingPoliantrian->KODE_ANTRIAN
            ])->orderBy("WAKTU","desc")->first();

            return response()->json([
                "metadata" => [
                    "code" => 200,
                    "message" => "Ok"
                ],"response" =>[
                    "namapoli" => $mappingPoliantrian->NAMA_POLI,
                    "totalantrean" => $antrian==null?0:$antrian->ID, /*Ambil Dari Antrian Sirspro*/
                    "jumlahterlayani" => $terpanggil==null?0:$terpanggil->NOMOR, /*Ambil Dari Antrian Sirspro*/
                    "lastupdate" => round(microtime(true) * 1000),
                    "lastupdatetanggal" => date("Y-m-d H:m:i"),
                ]
            ]);
        }catch (Exception $exception){
            return response()->json([
                "metadata" => [
                    "code" => $exception->getCode(),
                    "message" => $exception->getMessage()
            ]]);
        }

    }
}
