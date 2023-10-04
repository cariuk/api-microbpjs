<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class HistoryRequestMicrobpjs extends Model
{
    protected $table = 'history_request_microbpjs';

    protected $fillable = [
        'ID',
        'URL',
        'TANGGAL_REQUEST',
        'REQUEST',
    ];
}
