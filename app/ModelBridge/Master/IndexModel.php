<?php

namespace App\ModelBridge\Master;

use Illuminate\Database\Eloquent\Model;

class IndexModel extends Model{
    protected $connection = "master";
}
