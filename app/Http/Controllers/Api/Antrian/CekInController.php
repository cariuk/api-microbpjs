<?php

namespace App\Http\Controllers\Api\Antrian;

use App\Http\Controllers\Controller;
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
