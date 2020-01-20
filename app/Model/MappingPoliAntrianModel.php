<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class MappingPoliAntrianModel extends Model{
    protected $table = "mapping_poli_antrian";
    protected $primaryKey = "ID";
    public $incrementing = false;
    public $timestamps = false;

}
