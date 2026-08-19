<?php

namespace App\Http\Controllers\Api\Antrian;

use App\Http\Controllers\Controller;
use App\Model\AntrianOnlineV2Model;
use App\Model\MappingDPJPModel;
use App\Model\MappingPoliModel;
use App\ModelBridge\Master\PasienKartuAsuransiModel;
use App\ModelBridge\Master\PasienKartuIdentitasModel;
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
use Exception;

class PengambilanNomorController extends Controller
{
    function setData(Request $request)
    {
        // Get max booking days from config
        $maxBookingDays = config('antrian.max_booking_days_ahead', 90);
        $maxDate = date("Y-m-d", strtotime("+$maxBookingDays day"));

        // Validasi NIK hanya required jika tidak ada NORM
        $nikValidation = isset($request->norm) && !empty($request->norm)
            ? 'nullable|min:16|max:16'
            : 'required|min:16|max:16';

        $validator = Validator::make(
            $request->all(), [
            'nomorkartu' => 'required|min:13|max:13',
            'nik' => $nikValidation,
            'nohp' => 'required|max:13',
            'kodepoli' => 'required',
            'tanggalperiksa' => 'required|date_format:Y-m-d|after:' . date("Y-m-d", strtotime("-1 day")) . '|before:' . $maxDate,
            'kodedokter' => 'required',
            'jampraktek' => 'required',
            'jeniskunjungan' => 'required|in:1,2,3,4', //{1 (Rujukan FKTP), 2 (Rujukan Internal), 3 (Kontrol), 4 (Rujukan Antar RS)},
            'nomorreferensi' => 'required', //"{norujukan/kontrol pasien JKN,diisi kosong jika NON JKN}"
        ], [
            "nomorkartu.required" => "Nomor Kartu Peserta tidak boleh kosong",
            "nomorkartu.min" => "Nomor Kartu harus 13 digit",
            "nomorkartu.max" => "Nomor Kartu harus 13 digit",
            "nik.required" => "NIK tidak boleh kosong",
            "nik.min" => "NIK harus 16 digit",
            "nik.max" => "NIK harus 16 digit",
            "nohp.required" => "Nomor HP tidak boleh kosong",
            "nohp.max" => "Nomor HP maksimal 13 digit",
            "kodepoli.required" => "Kode Poli tidak boleh kosong",
            "tanggalperiksa.required" => "Tanggal Periksa tidak boleh kosong",
            "tanggalperiksa.date_format" => "Format Tanggal harus yyyy-mm-dd",
            "tanggalperiksa.after" => "Tanggal Periksa tidak boleh mundur dari hari ini",
            "tanggalperiksa.before" => "Tanggal Periksa hanya dapat dipilih maksimal $maxBookingDays hari ke depan (sampai $maxDate)",
            "kodedokter.required" => "Kode Dokter tidak boleh kosong",
            "jeniskunjungan.required" => "Jenis Kunjungan tidak boleh kosong",
            "jeniskunjungan.in" => "Jenis Kunjungan tidak valid (1=Rujukan FKTP, 2=Rujukan Internal, 3=Kontrol, 4=Rujukan Antar RS)",
            "nomorreferensi.required" => "Nomor Referensi/Nomor Rujukan tidak boleh kosong",
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
            $checkPasien = $checkPasien->where([
                "NORM" => $request->norm
            ])->first();

            // Jika tidak ditemukan berdasarkan NORM, cari berdasarkan NIK
            if ($checkPasien == null) {
                $checkByNIK = PasienKartuIdentitasModel::where([
                    "JENIS" => 1,
                    "NOMOR" => $request->nik
                ])->first();

                if ($checkByNIK != null) {
                    // Cek apakah NORM dari NIK punya kartu BPJS dengan nomor yang sesuai
                    $checkPasien = PasienKartuAsuransiModel::where([
                        "NOMOR" => $request->nomorkartu,
                        "JENIS" => 2,
                        "NORM" => $checkByNIK->NORM
                    ])->first();

                    // Jika kartu BPJS belum terdaftar untuk NORM ini, insert kartu BPJS baru
                    if ($checkPasien == null) {
                        $newKartuBPJS = new PasienKartuAsuransiModel();
                        $newKartuBPJS->NORM = $checkByNIK->NORM;
                        $newKartuBPJS->NOMOR = $request->nomorkartu;
                        $newKartuBPJS->JENIS = 2; // JENIS 2 untuk BPJS
                        $newKartuBPJS->save();

                        // Set checkPasien dengan data yang baru dibuat
                        $checkPasien = $newKartuBPJS;
                    }
                }

                if ($checkPasien == null) {
                    return response()->json([
                        "metadata" => [
                            "code" => 201,
                            "message" => "Data pasien tidak ditemukan. Silakan periksa kembali Nomor Kartu BPJS, NIK, dan Nomor RM Anda atau datang ke Front Office"
                        ]
                    ], 201);
                }
            }
        } else {
            $checkPasien = $checkPasien->first();

            // Jika kartu BPJS belum terdaftar, cek berdasarkan NIK
            if ($checkPasien == null) {
                // Cari pasien berdasarkan NIK
                $checkByNIK = PasienKartuIdentitasModel::where([
                    "JENIS" => 1,
                    "NOMOR" => $request->nik
                ])->first();

                // Jika pasien ditemukan berdasarkan NIK, insert kartu BPJS
                if ($checkByNIK != null) {
                    // Insert kartu BPJS baru untuk pasien ini
                    $newKartuBPJS = new PasienKartuAsuransiModel();
                    $newKartuBPJS->NORM = $checkByNIK->NORM;
                    $newKartuBPJS->NOMOR = $request->nomorkartu;
                    $newKartuBPJS->JENIS = 2; // JENIS 2 untuk BPJS
                    $newKartuBPJS->save();

                    // Set checkPasien dengan data yang baru dibuat
                    $checkPasien = $newKartuBPJS;
                } else {
                    // Jika NIK juga tidak ditemukan
                    return response()->json([
                        "metadata" => [
                            "code" => 202,
                            "message" => "Data pasien tidak ditemukan. Silakan mendaftar terlebih dahulu atau datang ke Front Office"
                        ]
                    ], 202);
                }
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
            ])->join("pendaftaran.penjamin", function ($join) {
                $join->on("antrian_ruangan.REF", "penjamin.NOPEN");
                $join->where("penjamin.JENIS", 2);
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

        if ($checkAntrian != null) {
            return response()->json([
                "metadata" => [
                    "code" => 200,
                    "message" => "Ok"
                ],
                "response" => [
                    "nomorantrean" => $checkAntrian->KODE_POLI . " " . $checkAntrian->NOMOR_ANTRIAN,
                    "angkaantrean" => $checkAntrian->NOMOR_ANTRIAN,
                    "kodebooking" => $checkAntrian->ID,
                    "norm" => $checkAntrian->NOMOR_RM,
                    "namapoli" => $mappingPoli->KODE . " - " . $mappingPoli->DESKRIPSI,
                    "namadokter" => $mappingDokter->NAMA,
                    "estimasidilayani" => $checkAntrian->ESTIMASI_DILAYANI,
                    "sisakuotajkn" => ($checkJadwalPraktek->KUOTA_ONSITE + $checkJadwalPraktek->ONLINE) - $terdaftar,
                    "kuotajkn" => $checkJadwalPraktek->KUOTA_ONSITE + $checkJadwalPraktek->ONLINE,
                    "sisakuotanonjkn" => 0,
                    "kuotanonjkn" => 0,
                    "keterangan" => "Peserta Harap 30 Menit Lebih Awal Guna Pencatatan Administrasi dan Annamesis Awal."
                ]
            ]);
        }
        /*=======================================================================================*/

        /*Check Existing Pendaftaran untuk hari yang sama*/
        $existingPendaftaran = PendaftaranModel::select(
            'pendaftaran.NOMOR',
            'pendaftaran.TANGGAL',
            'antrian_ruangan.NOMORDOKTER',
            'antrian_ruangan.TANGGAL as TANGGAL_ANTRIAN'
        )
        ->where('pendaftaran.NORM', $checkPasien->NORM)
        ->whereDate('pendaftaran.TANGGAL', $request->tanggalperiksa)
        ->where('pendaftaran.STATUS', 1)
        ->join('pendaftaran.tujuan_pasien', 'tujuan_pasien.NOPEN', '=', 'pendaftaran.NOMOR')
        ->join('pendaftaran.penjamin', 'penjamin.NOPEN', '=', 'pendaftaran.NOMOR')
        ->join('pendaftaran.antrian_ruangan', 'antrian_ruangan.REF', '=', 'pendaftaran.NOMOR')
        ->where('tujuan_pasien.RUANGAN', $checkJadwalPraktek->RUANGAN)
        ->where('tujuan_pasien.SHIFT', $checkJadwalPraktek->SHIFT)
        ->where('tujuan_pasien.DOKTER', $mappingDokter->DOKTER)
        ->where('penjamin.JENIS', 2)
        ->first();
        /*=======================================================================================*/

        DB::beginTransaction();
        try {

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
            if (strtotime(now()) >= strtotime($request->tanggalperiksa . " " . $checkJadwalPraktek->WAKTU_MULAI)) {
                $tanggalPendaftaran = now();
                $estimasi = Carbon::createFromTimestamp(strtotime($tanggalPendaftaran))->addMinutes(5)->timestamp * 1000;
            } else {
                $tanggalPendaftaran = date("Y-m-d H:i:s", strtotime($request->tanggalperiksa . " " . $checkJadwalPraktek->WAKTU_MULAI));
                $estimasi = null;
            }

            // Jika sudah ada pendaftaran, gunakan pendaftaran yang sudah ada
            if ($existingPendaftaran != null) {
                // Gunakan pendaftaran yang sudah ada
                $nomorPendaftaran = $existingPendaftaran->NOMOR;
                $nomorAntrian = $existingPendaftaran->NOMORDOKTER;
            } else {
                // Buat pendaftaran baru
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

                $nomorPendaftaran = $pendaftaran->NOMOR;
                $nomorAntrian = $antrian->NOMOR;
            }

            $new->NOPEN = $nomorPendaftaran;
            $new->NOMOR_ANTRIAN = $nomorAntrian; /*Antrian Poli*/
            $new->ESTIMASI_DILAYANI = $estimasi == null ? Carbon::createFromTimestamp(strtotime($tanggalPendaftaran))
                    ->addMinutes(5 * $new->NOMOR_ANTRIAN)->timestamp * 1000 : $estimasi;
            /*==========================================================*/
            $new->save();

            DB::commit();
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
            DB::rollBack();
            return response()->json([
                "metadata" => [
                    "code" => 500,
                    "message" => "Maaf, Terjadi Kesalahan Pada Sistem. Harap Coba Beberapa Saat Lagi"
                ], "response" => $exception->getMessage()
            ], 500);
        }
    }
}
