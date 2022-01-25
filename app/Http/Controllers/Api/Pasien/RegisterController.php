<?php

namespace App\Http\Controllers\Api\Pasien;

use App\Http\Controllers\Controller;
use App\Model\RegPasienModel;
use App\ModelBridge\Master\PasienKartuAsuransiModel;
use App\ModelBridge\Master\PasienModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use PHPUnit\Exception;

class RegisterController extends Controller{
    function setData(Request $request){
        $validator = Validator::make(
            $request->all(), [
            'nomorkartu' => 'required|min:13|max:13',
            'nik' => 'required|min:16|max:16',
            'nomorkk' => 'required|min:16|max:16',
            'nama' => 'required',
            'jeniskelamin' => 'required|in:L,P',
            'tanggallahir' => 'required|date_format:Y-m-d',
            'nohp' => 'required|max:13',
            'alamat' => 'required',
            'kodeprop' => 'required',
            'namaprop' => 'required',
            'kodedati2' => 'required',
            'namadati2' => 'required',
            'kodekec' => 'required',
            'namakec' => 'required',
            'kodekel' => 'required',
            'namakel' => 'required',
            'rw' => 'required',
            'rt' => 'required',
        ], [
            "nomorkartu.required" => "Nomor Kartu Peserta Tidak Boleh Kosong",
            "nomorkartu.min" => "Nomor Kartu Minimal 13 Digit",
            "nomorkartu.max" => "Nomor Kartu Maximal 13 Digit",
            "nik.required" => "Nomor Induk Kependudukan Tidak Boleh Kosong",
            "nik.min" => "Nomor Induk Kependudukan Minimal 16 Digit",
            "nik.max" => "Nomor Induk Kependudukan Maximal 16 Digit",
            "nomorkk.required" => "Nomor Induk Kependudukan Tidak Boleh Kosong",
            "nomorkk.min" => "Nomor Induk Kependudukan Minimal 16 Digit",
            "nomorkk.max" => "Nomor Induk Kependudukan Maximal 16 Digit",
            "nama.required" => "Nama Tidak Boleh Kosong",
            "jeniskelamin.required" => "Jenis Kelamin Tidak Boleh Kosong",
            "tanggallahir.required" => "Tanggal Lahir Tidak Boleh Kosong",
            "nohp.required" => "Nomor HP Tidak Boleh Kosong",
            "nohp.max" => "Nomor Handphone Maximal 13 Digit",
            "alamat.required" => "Alamat Tidak Boleh Kosong",
            "kodeprop.required" => "Kode Profinsi Tidak Boleh Kosong",
            "namaprop.required" => "Kode Profinsi Tidak Boleh Kosong",
            "kodedati2.required" => "Kode Dati Tidak Boleh Kosong",
            "namadati2.required" => "Nama Dati Tidak Boleh Kosong",
            "kodekec.required" => "Kode Kecamatan Tidak Boleh Kosong",
            "namakec.required" => "Nama Kecamatan Tidak Boleh Kosong",
            "kodekel.required" => "Kode Keluarahan Tidak Boleh Kosong",
            "namakel.required" => "Nama Kelurahan Tidak Boleh Kosong",
            "rw.required" => "RW Profinsi Tidak Boleh Kosong",
            "rt.required" => "RT Tidak Boleh Kosong",
        ]);

        if ($validator->fails()) {
            return response()->json([
                "metadata" => [
                    "code" => 201,
                    "message" => $validator->messages()->first()
                ]
            ], 201);
        }

        try {
            /*Check Nomor BPJS*/
            $checkNoKartu = PasienKartuAsuransiModel::where([
                "JENIS" => 2,
                "NOMOR" => $request->nomorkartu
            ])->first();

            if ($checkNoKartu != null){
                return response()->json([
                    "metadata" => [
                        "code" => 201,
                        "message" => "Nomor Kartu Telah Terdaftar Dengan Nomor RM ".$checkNoKartu->NORM
                    ]
                ], 201);
            }
            /*Input Di DB Master*/
            $newPasien = new PasienModel();
                $newPasien->NAMA = $request->nama;
                $newPasien->JENIS_KELAMIN = $request->jeniskelamin=="L"?1:2;
                $newPasien->TANGGAL_LAHIR = $request->tanggallahir;
                $newPasien->ALAMAT = $request->alamat ;
                $newPasien->RT = $request->rt ;
                $newPasien->RW = $request->rw ;
                $newPasien->KEWARGANEGARAAN = 71;
                $newPasien->TANGGAL = now();
                $newPasien->STATUS = 1;
            $newPasien->save();

            /*Proses Input Pasien Baru*/
            $new = new RegPasienModel();
                $new->NOMORKARTU = $request->nokartu;
                $new->NORM = $newPasien->NORM;
                $new->NIK = $request->nik;
                $new->NOMORKK = $request->nomorkk;
                $new->NAMA = $request->nama;
                $new->JENIS_KELAMIN = $request->jeniskelamin;
                $new->TANGGAL_LAHIR = $request->tanggallahir;
                $new->NOHP = $request->nohp;
                $new->ALAMAT = $request->alamat;
                $new->KODE_PROP = $request->kodeprop;
                $new->NAMA_PROP = $request->namaprop;
                $new->KODE_DATI2 = $request->kodedati2;
                $new->NAMA_DATI2 = $request->namadati2;
                $new->KODE_KEC = $request->kodekec;
                $new->NAMA_KEC = $request->namakec;
                $new->KODE_KEL = $request->kodekel;
                $new->NAMA_KEL = $request->namakel;
                $new->KODE_RW = $request->rw;
                $new->NAMA_RT = $request->rt;
            $new->save();
            return response()->json([
                "metadata" => [
                    "code" => 200,
                    "message" => "Harap datang ke admisi untuk melengkapi data rekam medis",
                ],
                "response" => [
                    "norm" => $new->NORM
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
}
