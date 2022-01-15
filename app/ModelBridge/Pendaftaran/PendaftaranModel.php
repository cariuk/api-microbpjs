<?php

namespace App\ModelBridge\Pendaftaran;

use Illuminate\Support\Facades\DB;

class PendaftaranModel extends IndexModel{
    protected $table = "pendaftaran";
    protected $primaryKey = "NOMOR";

    public static function generateNOMOR($tanggal){
        $data = DB::select(DB::raw("SELECT generator.generateNoPendaftaran('" . date("Y-m-d", strtotime($tanggal)) . "') ID"))[0];
        return $data->ID;
    }

}
