<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class JadwalOperasiController extends Controller
{
    function getData(Request $request){
        $validator = Validator::make(
            $request->all(), [
            'tanggalperiksa' => 'required',
            'kodepoli' => 'required',
        ],[]);

        if ($validator->fails()){
            return response()->json([
                "metadata" =>[
                    "status" => 422,
                    "message" => $validator->messages()->first()
                ]
            ],422);
        }
    }
}
