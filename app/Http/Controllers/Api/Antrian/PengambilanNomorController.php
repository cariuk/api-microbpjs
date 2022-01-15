<?php

namespace App\Http\Controllers\Api\Antrian;

use App\Http\Controllers\Controller;
use App\Model\AntrianOnlineModel;
use App\Model\MappingDPJPModel;
use App\Model\MappingPoliAntrianModel;
use App\Model\MappingPoliModel;
use App\ModelBridge\Cetakan\AntrianRJModel;
use App\ModelBridge\Cetakan\AntrianRJSMFModel;
use App\ModelBridge\Master\PasienKartuAsuransiModel;
use Grei\TanggalMerah;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PengambilanNomorController extends Controller{
    function setData(Request $request){
        $validator = Validator::make(
            $request->all(), [
            'nomorkartu' => 'required|min:13|max:13',
            'nik' => 'required|min:16|max:16',
            'nohp' =>  'required|max:13',
            'kodepoli' => 'required',
            'tanggalperiksa' => 'required|date_format:Y-m-d|after:today|before:'.date("Y-m-d",strtotime("+90 day")),
            'kodedokter' => 'required',
            'jampraktek' => 'required',
            'jeniskunjungan' => 'required|in:1,2,3,4', //{1 (Rujukan FKTP), 2 (Rujukan Internal), 3 (Kontrol), 4 (Rujukan Antar RS)},
            'nomorreferensi' => 'required', //"{norujukan/kontrol pasien JKN,diisi kosong jika NON JKN}"
        ],[
            "nomorkartu.required" => "Nomor Kartu Peserta Harus Terisi",
            "nomorkartu.min" => "Nomor Kartu Minimal 13 Digit",
            "nomorkartu.max" => "Nomor Kartu Maximal 13 Digit",
            "nik.required" => "Nomor Induk Kependudukan Harus Terisi",
            "nik.min" => "Nomor Induk Kependudukan Minimal 16 Digit",
            "nik.max" => "Nomor Induk Kependudukan Maximal 16 Digit",
            "nohp.required" => "Nomor HP Harus Terisi",
            "nohp.max" => "Nomor Handphone Maximal 13 Digit",
            "kodepoli.required" => "Kode Poli Harus Terisi",
            "tanggalperiksa.required" => "Tanggal Periksa Harus Terisi",
            "tanggalperiksa.date_format" => "Format Tanggal Periksa Harus Sesuai Format",
            "tanggalperiksa.after" => "Tanggal Periksa Hanya Boleh Dipilih H +1 Sampai H +90 Dari Tanggal ".date("Y-m-d"),
            "tanggalperiksa.before" => "Tanggal Periksa Hanya Boleh Dipilih H +1 Sampai H +90".date("Y-m-d"),
            "kodedokter.required" => "Kode Dokter Harus Terisi",
            "jeniskunjungan.required" => "Jenis Kunjungan Harus Terisi",
            "jeniskunjungan.in" => "Jenis Request Hanya Boleh 1 = Pendaftaran | 2 = Poli",
            "nomorreferensi.required" => "Nomor Referensi / Nomor Rujukan Harus Terisi",
        ]);

        if ($validator->fails()){
            return response()->json([
                "metadata" =>[
                    "code" => 400,
                    "message" => $validator->messages()->first()
                ]
            ],400);
        }

        /*Check Tanggal Merah*/
        $tanggal = new TanggalMerah();
        $tanggal->set_date(str_replace("-","",$request->tanggalperiksa));

        if (($tanggal->is_holiday())||($tanggal->is_sunday())){
            return response()->json([
                "metadata" =>[
                    "code" => 400,
                    "message" => "Maaf Tanggal Tersebut Masuk Dalam Tanggal Merah Atau Hari Libur"
                ]
            ],400);
        }

        /*Check Tanggal Pengambilan Antrian*/
        if ($request->tanggal==date("Y-m-d",strtotime("+1 day"))){
            if (strtotime(date('Y-m-d')." 18:00:00") < strtotime(date("Y-m-d H:i:s"))){
                return response()->json([
                    "metadata" =>[
                        "code" => 400,
                        "message" => "Maaf Pengambilan Nomor Antrian h+1"
                    ]
                ],400);
            }
        }

        /*Check Nomor Rekam Medik*/
        $checkPasien = PasienKartuAsuransiModel::where([
            "NOMOR" => $request->nomorkartu,
            "JENIS" => 2,
        ]);
        if (isset($request->norm)){
            $validator = Validator::make(
                $request->all(), [
                'norm' => 'numeric',
            ],[
                "norm.numeric" => "Nomor Rekam Medik Berupa Angka",
            ]);

            if ($validator->fails()){
                return response()->json([
                    "metadata" =>[
                        "code" => 400,
                        "message" => $validator->messages()->first()
                    ]
                ],400);
            }

            $checkPasien = $checkPasien->where([
                "NORM" => $request->norm
            ])->first();
            if ($checkPasien==null){
                return response()->json([
                    "metadata" =>[
                        "code" => 400,
                        "message" => "Maaf, Nomor BPJS Dan Nomor Rekam Medik Yang Dimasukkan Tidak Sesuai, Untuk Informasi Lebih Lanjut Harap Datang Ke Front Office Untuk Pencocokan Data Terlebih Dahulu"
                    ]
                ],400);
            }
        }else{
            $checkPasien = $checkPasien->first();
            if ($checkPasien==null){
                return response()->json([
                    "metadata" =>[
                        "code" => 400,
                        "message" => "Maaf, Nomor BPJS Anda Belum Terdaftar Pada Sistem Kami, Harap Membuat Nomor Rekam Medik Terlebih Dahulu Atau Bisa Datang Langsung Ke Front Office RSIA Ananda"
                    ]
                ],400);
            }


        }
        /*=======================================================================================*/

        /*CheckMapping*/
        $mappingPoliantrian = MappingPoliAntrianModel::where([
            "KODE_POLI" => $request->kodepoli
        ])->first();

        if ($mappingPoliantrian==null){
            return response()->json([
                "metadata" =>[
                    "code" => 400,
                    "message" => "Kode Poli Tidak Sesuai"
                ]
            ],400);
        }

        if ((($mappingPoliantrian->KODE_ANTRIAN==null)||($mappingPoliantrian->KODE_ANTRIAN==""))){
            return response()->json([
                "metadata" =>[
                    "code" => 400,
                    "message" => " Kode Poli Belum Tersedia Antriannya"
                ]
            ],400);
        }
        /*=======================================================================================*/

        /*Get Mapping SMF*/
        $mappingPoli = MappingPoliModel::where([
            "KODE" => $mappingPoliantrian->KODE_POLI
        ])->first();
        if ($mappingPoli==null){
            return response()->json([
                "metadata" =>[
                    "code" => 400,
                    "message" => "Kode Poli Tidak Sesuai"
                ]
            ],400);
        }
        /*=======================================================================================*/

        /*Get Mapping Kode Dokter Dengan Kode Dokter Internal*/
        $mappingDokter = MappingDPJPModel::where([
            "KODE" => $request->kodedokter
        ])->first();
        if ($mappingDokter==null){
            return response()->json([
                "metadata" =>[
                    "code" => 400,
                    "message" => "Maaf, Dokter Belum Tersedia"
                ]
            ],400);
        }
        /*=======================================================================================*/

        /*Check Data Antrian*/
        $checkAntrian = AntrianOnlineModel::where([
            "NOMOR_KARTU" => $request->nomorkartu,
            "KODE_POLI" => $request->kodepoli,
            "NOMOR_REFERENSI" => $request->nomorreferensi,
            "TANGGAL_PERIKSA" => $request->tanggalperiksa
        ])->first();
        /*=======================================================================================*/

    }
}