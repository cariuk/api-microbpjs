<?php

namespace App\Model;


use Illuminate\Database\Eloquent\Model;

class AntrianUpdateWaktuModel extends Model{

    protected $table = "antrian_update_waktu";

    public $incrementing = false;
    public $timestamps = false;

    protected $primaryKey = "ANTRIAN_ONLINE";

    protected $fillable = [
        'ANTRIAN_ONLINE',
        'TASK_ID',
        'WAKTU',
        'RESPONSE',
        'STATUS',
        'DATETIME'
    ];
}
