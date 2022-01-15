<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class AntrianOnlineV2Model extends Model{
    protected $table = "antrian_online";
    protected $primaryKey = "ID";
    public $incrementing = false;
    public $timestamps = false;

    public static function generateNOMOR(){
        $data = DB::select(DB::raw("SELECT generateNoReservasiAntrianOnlineV2() ID"))[0];
        return $data->ID;
    }
}
