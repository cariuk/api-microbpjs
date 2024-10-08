<?php

namespace App\Http\Controllers\Api\Antrian;

use App\Http\Controllers\Controller;
use App\Model\AntrianOnlineV2Model;
use App\Model\AntrianUpdateWaktuModel;
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
            "kodebooking.required" => "Kodebooking Tidak Boleh Kosong",
            "waktu.required" => "Waktu Cek In Tidak Boleh Kosong",
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

            /*Add Task Id 3*/
//            $checkAntrian = AntrianUpdateWaktuModel::where([
//                "ANTRIAN_ONLINE" => $request->kodebooking,
//                "TASK_ID" => 3
//            ])->first();

//            if ($checkAntrian == null) {
//                $newUpdate = new AntrianUpdateWaktuModel();
//                $newUpdate->ANTRIAN_ONLINE = $request->kodebooking;
//                $newUpdate->TASK_ID = 3;
//                $newUpdate->WAKTU = $request->waktu;
//                $newUpdate->RESPONSE = "{status:'OK'}";
//                $newUpdate->STATUS = 1;
//                $newUpdate->DATETIME = now();
//                $newUpdate->save();
//            }
            try{
                $newUpdate = new AntrianUpdateWaktuModel();
                $newUpdate->ANTRIAN_ONLINE = $request->kodebooking;
                $newUpdate->TASK_ID = 3;
                $newUpdate->WAKTU = $request->waktu;
                $newUpdate->RESPONSE = "{status:'OK'}";
                $newUpdate->STATUS = 1;
                $newUpdate->DATETIME = now();
                $newUpdate->save();
            }catch (Exception $exception){}

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
