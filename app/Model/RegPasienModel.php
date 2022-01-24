<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class RegPasienModel extends Model{
    protected $table = "reg_pasien";
    protected $primaryKey = "NOMORKARTU";
    public $incrementing = false;
    public $timestamps = false;

}
