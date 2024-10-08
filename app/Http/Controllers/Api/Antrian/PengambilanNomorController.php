<?php

namespace App\Http\Controllers\Api\Antrian;

use App\Http\Controllers\Controller;
use App\Model\AntrianOnlineV2Model;
use App\Model\MappingDPJPModel;
use App\Model\MappingPoliModel;
use App\ModelBridge\Master\PasienKartuAsuransiModel;
use App\ModelBridge\Pendaftaran\AntrianRuanganModel;
use App\ModelBridge\Pendaftaran\PendaftaranModel;
use App\ModelBridge\Pendaftaran\PendaftaranViaModel;
use App\ModelBridge\Pendaftaran\PenjaminModel;
use App\ModelBridge\Pendaftaran\TujuanModel;
use App\ModelBridge\Poliklinik\JadwalPraktekModel;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use PHPUnit\Exception;

class PengambilanNomorController extends Controller
{
    function setData(Request $request)
    {
        $validator = Validator::make(
            $request->all(), [
            'nomorkartu' => 'required|min:13|max:13',
            'nik' => 'required|min:16|max:16',
            'nohp' => 'required|max:13',
            'kodepoli' => 'required',
            'tanggalperiksa' => 'required|date_format:Y-m-d|after:' . date("Y-m-d", strtotime("-1 day")) . '|before:' . date("Y-m-d", strtotime("+90 day")),
            'kodedokter' => 'required',
            'jampraktek' => 'required',
            'jeniskunjungan' => 'required|in:1,2,3,4', //{1 (Rujukan FKTP), 2 (Rujukan Internal), 3 (Kontrol), 4 (Rujukan Antar RS)},
            'nomorreferensi' => 'required', //"{norujukan/kontrol pasien JKN,diisi kosong jika NON JKN}"
        ], [
            "nomorkartu.required" => "Nomor Kartu Peserta Tidak Boleh Kosong",
            "nomorkartu.min" => "Nomor Kartu Minimal 13 Digit",
            "nomorkartu.max" => "Nomor Kartu Maximal 13 Digit",
            "nik.required" => "Nomor Induk Kependudukan Tidak Boleh Kosong",
            "nik.min" => "Nomor Induk Kependudukan Minimal 16 Digit",
            "nik.max" => "Nomor Induk Kependudukan Maximal 16 Digit",
            "nohp.required" => "Nomor HP Tidak Boleh Kosong",
            "nohp.max" => "Nomor Handphone Maximal 13 Digit",
            "kodepoli.required" => "Kode Poli Tidak Boleh Kosong",
            "tanggalperiksa.required" => "Tanggal Periksa Tidak Boleh Kosong",
            "tanggalperiksa.date_format" => "Format Tanggal Tidak Sesuai, format yang benar adalah yyyy-mm-dd",
            "tanggalperiksa.after" => "Tanggal Periksa Hanya Boleh Dipilih H Sampai H +90 Dari Tanggal " . date("Y-m-d"),
            "tanggalperiksa.before" => "Tanggal Periksa Hanya Boleh Dipilih H Sampai H +90" . date("Y-m-d"),
            "kodedokter.required" => "Kode Dokter Tidak Boleh Kosong",
            "jeniskunjungan.required" => "Jenis Kunjungan Tidak Boleh Kosong",
            "jeniskunjungan.in" => "Jenis Request Hanya Boleh 1 = Pendaftaran | 2 = Poli",
            "nomorreferensi.required" => "Nomor Referensi / Nomor Rujukan Tidak Boleh Kosong",
        ]);

        if ($validator->fails()) {
            return response()->json([
                "metadata" => [
                    "code" => 201,
                    "message" => $validator->messages()->first()
                ]
            ], 201);
        }

        /*Check Nomor Rekam Medik*/
        $checkPasien = PasienKartuAsuransiModel::where([
            "NOMOR" => $request->nomorkartu,
            "JENIS" => 2,
        ]);

        if (isset($request->norm)) {
            $validator = Validator::make(
                $request->all(), [
                'norm' => 'numeric',
            ], [
                "norm.numeric" => "Nomor Rekam Medik Berupa Angka",
            ]);

            if ($validator->fails()) {
                return response()->json([
                    "metadata" => [
                        "code" => 201,
                        "message" => $validator->messages()->first()
                    ]
                ], 201);
            }

            $checkPasien = $checkPasien->where([
                "NORM" => $request->norm
            ])->first();
            if ($checkPasien == null) {
                return response()->json([
                    "metadata" => [
                        "code" => 201,
                        "message" => "Maaf, Nomor BPJS Dan Nomor Rekam Medik Yang Dimasukkan Tidak Sesuai, Untuk Informasi Lebih Lanjut Harap Datang Ke Front Office Untuk Pencocokan Data Terlebih Dahulu"
                    ]
                ], 201);
            }
        } else {
            $checkPasien = $checkPasien->first();
            if ($checkPasien == null) {
                return response()->json([
                    "metadata" => [
                        "code" => 202,
                        "message" => "Maaf, Nomor BPJS Anda Belum Terdaftar Pada Sistem Kami, Harap Membuat Nomor Rekam Medik Terlebih Dahulu Atau Bisa Datang Langsung Ke Front Office RSIA Ananda"
                    ]
                ], 202);
            }
        }
        /*=======================================================================================*/

        /*Get Mapping SMF*/
        $mappingPoli = MappingPoliModel::where([
            "KODE" => $request->kodepoli
        ])->first();
        if ($mappingPoli == null) {
            return response()->json([
                "metadata" => [
                    "code" => 201,
                    "message" => "Poli Tidak Ditemukan"
                ]
            ], 201);
        }
        /*=======================================================================================*/

        /*Get Mapping Kode Dokter Dengan Kode Dokter Internal*/
        $mappingDokter = MappingDPJPModel::select(
            "DOKTER",
            DB::raw("master.getNamaLengkapDokter(DOKTER) AS NAMA")
        )->where([
            "KODE" => $request->kodedokter
        ])->first();

        if ($mappingDokter == null) {
            return response()->json([
                "metadata" => [
                    "code" => 201,
                    "message" => "Maaf, Dokter Belum Tersedia"
                ]
            ], 201);
        }

        $haripraktek = date("N", strtotime($request->tanggalperiksa));
        $checkJadwalPraktek = JadwalPraktekModel::select(
            "jadwal_praktek.*",
            "dokter_smf.SMF"
        )->where([
            "dokter_smf.SMF" => $mappingPoli->SMF,
            "jadwal_praktek.BPJS" => 1,
            "jadwal_praktek.HARI" => $haripraktek,
            "jadwal_praktek.DOKTER" => $mappingDokter->DOKTER,
            "jadwal_praktek.STATUS" => 1
        ])->join("master.dokter_smf", "dokter_smf.DOKTER", "jadwal_praktek.DOKTER")
            ->first();


        if ($checkJadwalPraktek == null) {
            return response()->json([
                "metadata" => [
                    "code" => 201,
                    "message" => "Jadwal Dokter Tersebut Belum Tersedia, Silahkan Reschedule Tanggal dan Jam Praktek Lainnya"
                ]
            ], 201);
        }

        $terdaftar = AntrianRuanganModel::where("tanggal", $request->tanggalperiksa)
            ->where([
                "dokter" => $checkJadwalPraktek->DOKTER,
                "ruangan" => $checkJadwalPraktek->RUANGAN,
                "shift" => $checkJadwalPraktek->SHIFT
            ])->join("pendaftaran.penjamin",function ($join){
                $join->on("antrian_ruangan.REF","penjamin.NOPEN");
                $join->where("penjamin.JENIS",2);
            })->count();

        if ($terdaftar >= $checkJadwalPraktek->KUOTA_ONLINE) {
            return response()->json([
                "metadata" => [
                    "code" => 201,
                    "message" => "Kuota Jadwal Dokter Tersebut Telah Penuh, Silahkan Reschedule Tanggal dan Jam Praktek Lainnya"
                ]
            ], 201);
        }
        /*=======================================================================================*/

        /*Check Data Antrian*/
        $checkAntrian = AntrianOnlineV2Model::where([
            "TANGGAL_PERIKSA" => $request->tanggalperiksa,
            "NOMOR_KARTU" => $request->nomorkartu,
            "KODE_POLI" => $request->kodepoli,
            "STATUS" => 1
        ])->first();
        /*=======================================================================================*/
        try {
            if ($checkAntrian != null) {
                return response()->json([
                    "metadata" => [
                        "code" => 201,
                        "message" => "Nomor Antrean Hanya Dapat Diambil 1 Kali Pada Tanggal Yang Sama"
                    ],
                    "response" => $checkAntrian
                ], 201);
            }

            $new = new AntrianOnlineV2Model();
            $new->ID = AntrianOnlineV2Model::generateNOMOR();
            $new->NOMOR_KARTU = $request->nomorkartu;
            $new->NIK = $request->nik;
            $new->KODE_POLI = $request->kodepoli;
            $new->NOMOR_RM = $checkPasien->NORM;
            $new->NO_HP = $request->nohp;
            $new->TANGGAL_PERIKSA = $request->tanggalperiksa;
            $new->KODE_DOKTER = $request->kodedokter;
            $new->JAM_PRAKTEK = $request->jampraktek;
            $new->JENIS_KUNJUNGAN = $request->jeniskunjungan;
            $new->NOMOR_REFERENSI = $request->nomorreferensi;
            $new->STATUS = 1;
            $new->TANGGAL_BUAT = now();

            /*Get Nomor Antrian SIMRS*/
            if (strtotime(now()) >= strtotime($request->tanggalperiksa . " " . $checkJadwalPraktek->WAKTU_MULAI)){
                $tanggalPendaftaran = now();
                $estimasi = Carbon::createFromTimestamp(strtotime($tanggalPendaftaran))->addMinutes(5)->timestamp * 1000;
            } else{
                $tanggalPendaftaran = date("Y-m-d H:i:s", strtotime($request->tanggalperiksa . " " . $checkJadwalPraktek->WAKTU_MULAI));
                $estimasi = null;
            }

            //Pendaftaran Pasien
            $pendaftaran = new PendaftaranModel();
            $pendaftaran->NOMOR = PendaftaranModel::generateNOMOR(date("Y-m-d", strtotime($request->tanggalperiksa)));
            $pendaftaran->NORM = $checkPasien->NORM;
            $pendaftaran->TANGGAL = $tanggalPendaftaran;
            $pendaftaran->DIAGNOSA_MASUK = "Z00.0";
            $pendaftaran->OLEH = 1;
            $pendaftaran->STATUS = 1;
            $pendaftaran->save();

            //Pendaftaran Via
            $viapendaftaran = new PendaftaranViaModel();
            $viapendaftaran->NOPEN = $pendaftaran->NOMOR;
            $viapendaftaran->JENIS = 4;
            $viapendaftaran->save();

            //Tujuan Pendaftaran
            $tujuan = new TujuanModel();
            $tujuan->NOPEN = $pendaftaran->NOMOR;
            $tujuan->RUANGAN = $checkJadwalPraktek->RUANGAN;
            $tujuan->SMF = $mappingPoli->SMF;
            $tujuan->SHIFT = $checkJadwalPraktek->SHIFT;
            $tujuan->DOKTER = $mappingDokter->DOKTER;
            $tujuan->save();

            //Penjamin Pendaftaran
            $penjamin = new PenjaminModel();
            $penjamin->NOPEN = $pendaftaran->NOMOR;
            $penjamin->JENIS = 2;
            $penjamin->NOMOR = "";
            $penjamin->KELAS = 0;
            $penjamin->save();

            $antrian = AntrianRuanganModel::select(
                "TANGGAL",
                "NOMORDOKTER as NOMOR"
            )->where([
                "REF" => $pendaftaran->NOMOR
            ])->first();

            $new->NOPEN = $pendaftaran->NOMOR;
            $new->NOMOR_ANTRIAN = $antrian->NOMOR; /*Antrian Poli*/
            $new->ESTIMASI_DILAYANI = $estimasi == null ? Carbon::createFromTimestamp(strtotime($tanggalPendaftaran))
                    ->addMinutes(5 * $new->NOMOR_ANTRIAN)->timestamp * 1000 : $estimasi;
            /*==========================================================*/
            $new->save();

            return response()->json([
                "metadata" => [
                    "code" => 200,
                    "message" => "Ok"
                ],
                "response" => [
                    "nomorantrean" => $new->KODE_POLI . " " . $new->NOMOR_ANTRIAN,
                    "angkaantrean" => $new->NOMOR_ANTRIAN,
                    "kodebooking" => $new->ID,
                    "norm" => $new->NOMOR_RM, /*Nomor RM*/
                    "namapoli" => $mappingPoli->KODE . " - " . $mappingPoli->DESKRIPSI, /*Nama Poli*/
                    "namadokter" => $mappingDokter->NAMA,
                    "estimasidilayani" => $new->ESTIMASI_DILAYANI, /*Waktu Pelayanan*/
                    "sisakuotajkn" => ($checkJadwalPraktek->KUOTA_ONSITE + $checkJadwalPraktek->ONLINE) - $terdaftar,
                    "kuotajkn" => $checkJadwalPraktek->KUOTA_ONSITE + $checkJadwalPraktek->ONLINE,
                    "sisakuotanonjkn" => 0,
                    "kuotanonjkn" => 0,
                    "keterangan" => "Peserta Harap 30 Menit Lebih Awal Guna Pencatatan Administrasi dan Annamesis Awal."
                ]
            ]);
        } catch (Exception $exception) {
            return response()->json([
                "metadata" => [
                    "code" => 500,
                    "message" => "Maaf, Terjadi Kesalahan Pada Sistem. Harap Coba Beberapa Saat Lagi"
                ], "response" => $exception->getMessage()
            ], 500);
        }
    }
}
