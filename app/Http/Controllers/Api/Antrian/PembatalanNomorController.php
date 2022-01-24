<?php

namespace App\Http\Controllers\Api\Antrian;

use App\Http\Controllers\Controller;
use App\Model\AntrianOnlineV2Model;
use App\ModelBridge\Pendaftaran\PendaftaranModel;
use App\ModelBridge\Pendaftaran\TujuanModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use PHPUnit\Exception;

class PembatalanNomorController extends Controller{
    function setData(Request $request){
        $validator = Validator::make(
            $request->all(), [
            'kodebooking' => 'required',
            'keterangan' => 'required',
        ],[
            "kodebooking.required" => "Kodebooking Tidak Boleh Kosong",
            "keterangan.required" => "Keterangan Tidak Boleh Kosong",
        ]);

        if ($validator->fails()){
            return response()->json([
                "metadata" =>[
                    "code" => 201,
                    "message" => $validator->messages()->first()
                ]
            ],201);
        }
        try{
            $checkAntrian = AntrianOnlineV2Model::where([
                "ID" => $request->kodebooking,
                "STATUS" => 1
            ])->first();

            if ($checkAntrian == null){
                return response()->json([
                    "metadata" =>[
                        "code" => 201,
                        "message" => "Antrean Tidak Ditemukan Atau Sudah DiBatalakan"
                    ]
                ],201);
            }

            if ($checkAntrian->CEKIN!=null){
                return response()->json([
                    "metadata" =>[
                        "code" => 201,
                        "message" => "Antrean Tidak Dapat Dibatalkan Karena Sudah Cek In",
                    ]
                ],201);
            }

            $checkTujuan = TujuanModel::where([
                "NOPEN" => $checkAntrian->NOPEN
            ])->first();

            if ($checkTujuan->STATUS==2){
                return response()->json([
                    "metadata" =>[
                        "code" => 201,
                        "message" => "Pasien Sudah Dilayani, Antrean Tidak Dapat Dibatalkan",
                    ]
                ],201);
            }

            AntrianOnlineV2Model::where([
                "ID" => $request->kodebooking,
                "STATUS" => 1
            ])->whereNull("CEKIN")->update([
                "STATUS" => 0,
                "ALASAN_PEMBATALAN" => $request->keterangan,
                "WAKTU_PEMBATALAN" => now()
            ]);

            return response()->json([
                "metadata" =>[
                    "code" => 200,
                    "message" => "OK"
                ]
            ]);
        }catch (Exception $exception){
            return response()->json([
                "metadata" =>[
                    "code" => 500,
                    "message" => "Telah Terjadi Kesalahaan!"
                ],
                "response" => $exception->getMessage()
            ],500);
        }
    }
}
