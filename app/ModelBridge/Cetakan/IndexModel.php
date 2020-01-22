<?php

namespace App\ModelBridge\Cetakan;

use Illuminate\Database\Eloquent\Model;

class IndexModel extends Model{
    protected $connection = "cetakan";
    public $timestamps = false;
}
