<?php

namespace App\ModelBridge\Pendaftaran;

use Illuminate\Database\Eloquent\Model;

class IndexModel extends Model{
    protected $connection = "pendaftaran";
    public $timestamps = false;
    public $incrementing = false;
}
