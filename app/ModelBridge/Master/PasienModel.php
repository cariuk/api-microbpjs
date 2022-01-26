<?php

namespace App\ModelBridge\Master;

class PasienModel extends IndexModel {
    protected $table = "pasien";
    protected $primaryKey = "NORM";
    public $timestamps = false;
}
