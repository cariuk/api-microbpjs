<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class AntrianOnlineModel extends Model{
    protected $table = "antrian_online";
    protected $primaryKey = "ID";
    public $incrementing = false;
    public $timestamps = false;

    public static function generateNOMOR(){
        $data = DB::select(DB::raw("SELECT generateNoReservasi() ID"))[0];
        return $data->ID;
    }
}
