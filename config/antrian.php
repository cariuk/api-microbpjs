<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Antrian Configuration
    |--------------------------------------------------------------------------
    |
    | Konfigurasi untuk sistem antrian online BPJS
    |
    */

    /*
     * Maksimal berapa hari ke depan pasien bisa mengambil nomor antrian
     * Default: 90 hari
     */
    'max_booking_days_ahead' => env('MAX_BOOKING_DAYS_AHEAD', 90),

];