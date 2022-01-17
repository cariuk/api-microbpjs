<?php

namespace App\Http\Controllers\Api\Antrian;

use App\Http\Controllers\Controller;
use App\Model\AntrianOnlineV2Model;
use App\Model\MappingDPJPModel;
use App\Model\MappingPoliAntrianModel;
use App\Model\MappingPoliModel;
use App\ModelBridge\Pendaftaran\AntrianRuanganModel;
use App\ModelBridge\Pendaftaran\TujuanModel;
use App\ModelBridge\Poliklinik\JadwalPraktekModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use PHPUnit\Exception;

class InformasiController extends Controller{
    function getStatus(Request $request){
        $validator = Validator::make(
            $request->all(), [
            'kodepoli' => 'required',
            'kodedokter' => 'required',
            'tanggalperiksa' => 'required|date_format:Y-m-d',
            'jampraktek' => 'required',
        ],[
            "kodepoli.required" => "Kode Poli Harus Terisi",
            "kodedokter.required" => "Kode Dokter Harus Terisi",
            "tanggalperiksa.required" => "Tanggal Periksa Harus Terisi",
            "tanggalperiksa.date_format" => "Format Tanggal Tidak Sesuai (Y-m-d)",
            "jampraktek.required" => "Jam Peraktek Harus Terisi",
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
            /*Check Data Antrian*/
            $checkAntrian = AntrianOnlineV2Model::where([
                "KODE_POLI" => $request->kodepoli,
                "KODE_DOKTER" => $request->kodedokter,
                "TANGGAL_PERIKSA" => $request->tanggalperiksa,
                "JAM_PRAKTEK" => $request->jampraktek
            ])->first();

            if ($checkAntrian==null){
                return response()->json([
                    "metadata" => [
                        "code" => 404,
                        "message" => "Maaf! Anda Belum Mengambil Nomor Antrian"
                    ]
                ]);
            }

            /*CheckMapping*/
            $mappingPoliantrian = MappingPoliAntrianModel::where([
                "KODE_POLI" => $request->kodepoli
            ])->first();

            if ($mappingPoliantrian == null) {
                return response()->json([
                    "metadata" => [
                        "code" => 400,
                        "message" => "Kode Poli Tidak Sesuai"
                    ]
                ], 400);
            }

            if ((($mappingPoliantrian->KODE_ANTRIAN == null) || ($mappingPoliantrian->KODE_ANTRIAN == ""))) {
                return response()->json([
                    "metadata" => [
                        "code" => 400,
                        "message" => " Kode Poli Belum Tersedia Antriannya"
                    ]
                ], 400);
            }
            /*=======================================================================================*/
            /*Get Mapping SMF*/
            $mappingPoli = MappingPoliModel::where([
                "KODE" => $mappingPoliantrian->KODE_POLI
            ])->first();
            if ($mappingPoli == null) {
                return response()->json([
                    "metadata" => [
                        "code" => 400,
                        "message" => "Kode Poli Tidak Sesuai"
                    ]
                ], 400);
            }
            /*=======================================================================================*/

            $mappingDokter = MappingDPJPModel::select(
                "DOKTER",
                DB::raw("master.getNamaLengkapDokter(DOKTER) AS NAMA")
            )->where([
                "KODE" => $request->kodedokter
            ])->first();
            if ($mappingDokter == null) {
                return response()->json([
                    "metadata" => [
                        "code" => 400,
                        "message" => "Maaf, Dokter Belum Tersedia"
                    ]
                ], 400);
            }
            $haripraktek = date("N", strtotime($request->tanggalperiksa));
            $checkJadwalPraktek = JadwalPraktekModel::where([
                "BPJS" => 1,
                "HARI" => $haripraktek,
                "DOKTER" => $mappingDokter->DOKTER,
                "STATUS" => 1
            ])->first();

            $terdaftar = AntrianRuanganModel::where("tanggal", $request->tanggalperiksa)
            ->where([
                "dokter" => $checkJadwalPraktek->DOKTER,
                "ruangan" => $checkJadwalPraktek->RUANGAN,
                "shift" => $checkJadwalPraktek->SHIFT
            ])->count();

            $terlayani = AntrianRuanganModel::select(
                "antrian_ruangan.NOMORDOKTER",
                "pendaftaran.STATUS"
            )->where("antrian_ruangan.tanggal", $request->tanggalperiksa)
            ->where([
                "dokter" => $checkJadwalPraktek->DOKTER,
                "ruangan" => $checkJadwalPraktek->RUANGAN,
                "shift" => $checkJadwalPraktek->SHIFT
            ])->join("pendaftaran", function ($join){
               $join->on("pendaftaran.NOMOR","antrian_ruangan.REF")->whereIn(
                   "pendaftaran.STATUS",[0,2]
               );
            })->orderBy("NOMORDOKTER","DESC")->first();

            $keterangan = ($terlayani->NOMORDOKTER-$checkAntrian->NOMOR_ANTRIAN)<=0?"Nomor Antrian Anda Sudah Terpainggil / Terlewatkan Silahkan Melaporkan Diri Anda Kepetugas Kami ":"Peserta Harap 30 Menit Lebih Awal Guna Pencatatan Administrasi dan Annamesis Awal.";
            return response()->json([
                "metadata" => [
                    "code" => 200,
                    "message" => "Ok"
                ],
                "response" => [
                    "namapoli" => $mappingPoli->KODE." - ".$mappingPoli->DESKRIPSI, /*Nama Poli*/
                    "namadokter" => $mappingDokter->NAMA,
                    "totalantrean" => $terdaftar, /*Total Antrian*/
                    "sisaantrean" => ($terlayani->NOMORDOKTER-$checkAntrian->NOMOR_ANTRIAN)<=0?0:$terlayani->NOMORDOKTER-$checkAntrian->NOMOR_ANTRIAN,
                    "antreanpanggil" => $checkAntrian->KODE_POLI." ".$checkAntrian->NOMOR_ANTRIAN,
                    "sisakuotajkn" => ($checkJadwalPraktek->KUOTA_ONSITE+$checkJadwalPraktek->ONLINE)-$terdaftar,
                    "kuotajkn" => $checkJadwalPraktek->KUOTA_ONSITE+$checkJadwalPraktek->ONLINE,
                    "sisakuotanonjkn" => 0,
                    "kuotanonjkn" => 0,
                    "keterangan" => $keterangan
                ]
            ]);
        }catch (Exception $exception) {
            return response()->json([
                "metadata" => [
                    "code" => 500,
                    "message" => "Maaf, Terjadi Kesalahan Pada Sistem. Harap Coba Beberapa Saat Lagi"
                ],"response" => $exception->getMessage()
            ], 500);
        }
    }

    function getSisaNomor(Request $request){

    }
}
