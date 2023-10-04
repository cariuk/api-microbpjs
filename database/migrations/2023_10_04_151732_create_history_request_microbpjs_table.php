<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateHistoryRequestMicrobpjsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('history_request_microbpjs', function (Blueprint $table) {
            $table->bigIncrements('ID');
            $table->string('URL');
            $table->dateTime('TANGGAL_REQUEST');
            $table->text('REQUEST');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('history_request_microbpjs');
    }
}
