<?php

namespace App\Http\Controllers\Api\JadwalOperasi;

use App\Http\Controllers\Controller;
use App\Model\HistoryRequestMicrobpjs;
use App\ModelBridge\Pendaftaran\JadwalOperasiModel;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ListDataController extends Controller
{
    function getData(Request $request)
    {
        $validator = Validator::make(
            $request->all(), [
            'nopeserta' => 'required|min:13|max:13',
        ], []);

        if ($validator->fails()) {
            return response()->json([
                "metadata" => [
                    "code" => 400,
                    "message" => $validator->messages()->first()
                ]
            ], 400);
        }

        $jadwalOperasi = JadwalOperasiModel::select(
            "jadwal_operasi.BOOKING as kodebooking",
            "jadwal_operasi.TANGGAL as tanggaloperasi",
            "jadwal_operasi.TINDAKAN as jenistindakan",
            "bpm.KODE as kodepoli",
            "bpm.DESKRIPSI as namapoli",
            DB::raw("IF(jadwal_operasi.STATUS=2,1,0) as terlaksana")
        )
            ->join("pendaftaran.pendaftaran as ppen", "ppen.NOMOR", "jadwal_operasi.NOPEN")
            ->join("pendaftaran.penjamin as pp", function ($join) {
                $join->on("pp.NOPEN", "jadwal_operasi.NOPEN")->where(
                    "pp.JENIS", 2
                );
            })
            ->join("bpjs.poli_mapping as bpm", "bpm.SMF", "jadwal_operasi.SMF")
            ->where(DB::raw("master.getKartuAsuransiPasien(ppen.NORM,pp.JENIS)"), $request->nopeserta)
            ->where([
                "jadwal_operasi.status" => 1
            ])->orderBy("jadwal_operasi.TANGGAL")->get();

        if ($jadwalOperasi == null) {
            return response()->json([
                "metadata" => [
                    "code" => 404,
                    "message" => "Tidak Ada Jadwal Operasi"
                ]
            ], 404);
        }

        /*record request*/
        try{
            $history = new HistoryRequestMicrobpjs();
            $history->URL = $request->url();
            $history->TANGGAL_REQUEST = now();
            $history->REQUEST = json_encode($request->all());
            $history->save();
        }catch (\Exception $exception){}

        return response()->json([
            "metadata" => [
                "code" => 200,
                "message" => "Ok"
            ], "response" => [
                "list" => $jadwalOperasi
            ]
        ]);
    }

    function getDataByTanggal(Request $request)
    {
        $validator = Validator::make(
            $request->all(), [
            'tanggalawal' => 'required|date_format:Y-m-d',
            'tanggalakhir' => 'required|date_format:Y-m-d|after:' . date("Y-m-d", strtotime($request->tanggalawal) - 1),

        ], []);

        if ($validator->fails()) {
            return response()->json([
                "metadata" => [
                    "code" => 400,
                    "message" => $validator->messages()->first()
                ]
            ], 400);
        }

        $tanggalAwal = Carbon::make($request->tanggalawal);
        $jadwalOperasi = JadwalOperasiModel::select(
            "jadwal_operasi.BOOKING as kodebooking",
            "jadwal_operasi.TANGGAL as tanggaloperasi",
            "jadwal_operasi.TINDAKAN as jenistindakan",
            "bpm.KODE as kodepoli",
            "bpm.DESKRIPSI as namapoli",
            DB::raw("IF(jadwal_operasi.STATUS=2,1,0) as terlaksana"),
            DB::raw("IF(pp.JENIS=2,master.getKartuAsuransiPasien(ppen.NORM,pp.JENIS),'') as nopeserta"),
            DB::raw("CAST(ROUND((UNIX_TIMESTAMP(CURTIME(3))* 1000),0) AS UNSIGNED) as lastupdate")
        )->join("pendaftaran.pendaftaran as ppen", "ppen.NOMOR", "jadwal_operasi.NOPEN")
            ->leftJoin("pendaftaran.penjamin as pp", function ($join) {
                $join->on("pp.NOPEN", "jadwal_operasi.NOPEN")->where(
                    "pp.JENIS", 2
                );
            })->join("bpjs.poli_mapping as bpm", "bpm.SMF", "jadwal_operasi.SMF")
            ->whereBetween("jadwal_operasi.TANGGAL", [$request->tanggalawal, $request->tanggalakhir])
            ->orderBy("jadwal_operasi.TANGGAL")->get();

        /*record request*/
        try{
            $history = new HistoryRequestMicrobpjs();
            $history->URL = $request->url();
            $history->TANGGAL_REQUEST = now();
            $history->REQUEST = json_encode($request->all());
            $history->save();
        }catch (\Exception $exception){}

        return response()->json([
            "metadata" => [
                "code" => 200,
                "message" => "Ok"
            ], "response" => [
                "list" => $jadwalOperasi
            ]
        ]);
    }

}
