<?php

namespace App\Http\Controllers\Api\Antrian;

use App\Http\Controllers\Controller;
use App\Model\AntrianOnlineV2Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use PHPUnit\Exception;

class CekInController extends Controller{
    function setData(Request $request){
        $validator = Validator::make(
            $request->all(), [
            'kodebooking' => 'required',
            'waktu' => 'required',
        ],[
            "kodebooking.required" => "Kodebooking Harus Terisi",
            "waktu.required" => "Waktu Cek In Harus Terisi",
        ]);

        if ($validator->fails()){
            return response()->json([
                "metadata" =>[
                    "code" => 400,
                    "message" => $validator->messages()->first()
                ]
            ],400);
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
                        "message" => "Antrean Sudah Cek In",
                    ]
                ],201);
            }
            AntrianOnlineV2Model::where([
                "ID" => $request->kodebooking,
                "STATUS" => 1
            ])->update([
                "CEKIN" => $request->waktu
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
