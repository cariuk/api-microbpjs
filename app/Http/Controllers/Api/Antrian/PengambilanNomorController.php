<?php

namespace App\Http\Controllers\Api\Antrian;

use App\Http\Controllers\Controller;
use App\Model\AntrianOnlineV2Model;
use App\Model\MappingDPJPModel;
use App\Model\MappingPoliAntrianModel;
use App\Model\MappingPoliModel;
use App\ModelBridge\Master\PasienKartuAsuransiModel;
use App\ModelBridge\Pendaftaran\AntrianRuanganModel;
use App\ModelBridge\Pendaftaran\PendaftaranModel;
use App\ModelBridge\Pendaftaran\PendaftaranViaModel;
use App\ModelBridge\Pendaftaran\PenjaminModel;
use App\ModelBridge\Pendaftaran\TujuanModel;
use App\ModelBridge\Poliklinik\JadwalPraktekModel;
use Grei\TanggalMerah;
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
            'tanggalperiksa' => 'required|date_format:Y-m-d|after:today|before:' . date("Y-m-d", strtotime("+90 day")),
            'kodedokter' => 'required',
            'jampraktek' => 'required',
            'jeniskunjungan' => 'required|in:1,2,3,4', //{1 (Rujukan FKTP), 2 (Rujukan Internal), 3 (Kontrol), 4 (Rujukan Antar RS)},
            'nomorreferensi' => 'required', //"{norujukan/kontrol pasien JKN,diisi kosong jika NON JKN}"
        ], [
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
            "tanggalperiksa.date_format" => "Format Tanggal Tidak Sesuai, format yang benar adalah yyyy-mm-dd",
            "tanggalperiksa.after" => "Tanggal Periksa Hanya Boleh Dipilih H +1 Sampai H +90 Dari Tanggal " . date("Y-m-d"),
            "tanggalperiksa.before" => "Tanggal Periksa Hanya Boleh Dipilih H +1 Sampai H +90" . date("Y-m-d"),
            "kodedokter.required" => "Kode Dokter Harus Terisi",
            "jeniskunjungan.required" => "Jenis Kunjungan Harus Terisi",
            "jeniskunjungan.in" => "Jenis Request Hanya Boleh 1 = Pendaftaran | 2 = Poli",
            "nomorreferensi.required" => "Nomor Referensi / Nomor Rujukan Harus Terisi",
        ]);

        if ($validator->fails()) {
            return response()->json([
                "metadata" => [
                    "code" => 201,
                    "message" => $validator->messages()->first()
                ]
            ], 201);
        }

        /*Check Tanggal Merah*/
        $tanggal = new TanggalMerah();
        $tanggal->set_date(str_replace("-", "", $request->tanggalperiksa));

        if (($tanggal->is_holiday()) || ($tanggal->is_sunday())) {
            return response()->json([
                "metadata" => [
                    "code" => 201,
                    "message" => "Maaf Tanggal Tersebut Masuk Dalam Tanggal Merah Atau Hari Libur"
                ]
            ], 201);
        }

        /*Check Tanggal Pengambilan Antrian*/
        if ($request->tanggal == date("Y-m-d", strtotime("+1 day"))) {
            if (strtotime(date('Y-m-d') . " 18:00:00") < strtotime(date("Y-m-d H:i:s"))) {
                return response()->json([
                    "metadata" => [
                        "code" => 201,
                        "message" => "Maaf Pengambilan Nomor Antrian h+1"
                    ]
                ], 201);
            }
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
                        "code" => 201,
                        "message" => "Maaf, Nomor BPJS Anda Belum Terdaftar Pada Sistem Kami, Harap Membuat Nomor Rekam Medik Terlebih Dahulu Atau Bisa Datang Langsung Ke Front Office RSIA Ananda"
                    ]
                ], 201);
            }


        }
        /*=======================================================================================*/

        /*CheckMapping*/
        $mappingPoliantrian = MappingPoliAntrianModel::where([
            "KODE_POLI" => $request->kodepoli
        ])->first();

        if ($mappingPoliantrian == null) {
            return response()->json([
                "metadata" => [
                    "code" => 201,
                    "message" => "Poli Tidak Ditemukan"
                ]
            ], 201);
        }

        if ((($mappingPoliantrian->KODE_ANTRIAN == null) || ($mappingPoliantrian->KODE_ANTRIAN == ""))) {
            return response()->json([
                "metadata" => [
                    "code" => 201,
                    "message" => "Poli Tidak Ditemukan"
                ]
            ], 201);
        }
        /*=======================================================================================*/

        /*Get Mapping SMF*/
        $mappingPoli = MappingPoliModel::where([
            "KODE" => $mappingPoliantrian->KODE_POLI
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
                    ]
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
            try{
                //Pendaftaran Pasien
                $pendaftaran = new PendaftaranModel();
                $pendaftaran->NOMOR = PendaftaranModel::generateNOMOR(date("Y-m-d", strtotime($request->tanggalperiksa)));
                $pendaftaran->NORM = $checkPasien->NORM;
                $pendaftaran->TANGGAL = date("Y-m-d H:i:s", strtotime($request->tanggalperiksa." ".$checkJadwalPraktek->WAKTU_MULAI));
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
                $tujuan->RUANGAN =  $checkJadwalPraktek->RUANGAN;
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
            }catch (Exception $exception) {
                return response()->json([
                    "metadata" => [
                        "code" => 500,
                        "message" => "Maaf, Terjadi Kesalahan Pada Sistem. Harap Coba Beberapa Saat Lagi"
                    ],"response" => $exception->getMessage()
                ], 500);
            }

            $new->NOPEN = $pendaftaran->NOMOR;
            $new->NOMOR_ANTRIAN = $antrian->NOMOR; /*Antrian Poli*/
            $new->ESTIMASI_DILAYANI = strtotime("+".(5*$new->NOMOR_ANTRIAN)." minutes",strtotime($request->tanggalperiksa." ".$checkJadwalPraktek->WAKTU_MULAI))*1000;
                /*==========================================================*/
            $new->save();

            return response()->json([
                "metadata" => [
                    "code" => 200,
                    "message" => "Ok"
                ],
                "response" => [
                    "nomorantrean" => $new->KODE_POLI." ".$new->NOMOR_ANTRIAN,
                    "angkaantrean" => $new->NOMOR_ANTRIAN,
                    "kodebooking" =>  $new->ID,
                    "norm" => $new->NOMOR_RM, /*Nomor RM*/
                    "namapoli" => $mappingPoli->KODE." - ".$mappingPoli->DESKRIPSI, /*Nama Poli*/
                    "namadokter" => $mappingDokter->NAMA,
                    "estimasidilayani" => $new->ESTIMASI_DILAYANI, /*Waktu Pelayanan*/
                    "sisakuotajkn" => ($checkJadwalPraktek->KUOTA_ONSITE+$checkJadwalPraktek->ONLINE)-$terdaftar,
                    "kuotajkn" => $checkJadwalPraktek->KUOTA_ONSITE+$checkJadwalPraktek->ONLINE,
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
                ],"response" => $exception->getMessage()
            ], 500);
        }
    }
}
