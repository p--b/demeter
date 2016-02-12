<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class Refunds extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('booking_seats', function ($tbl)
        {
            $tbl->integer('refundAmount')->default(0)->unsigned();
            $tbl->boolean('void')->default(FALSE);
        });

        Schema::create('refunds', function($tbl)
        {
            $tbl->increments('id');
            $tbl->integer('booking_id')->unsigned();
            $tbl->integer('net')->unsigned();
            $tbl->integer('fees')->unsigned();
            $tbl->integer('gross')->unsigned();
            $tbl->timestamps();

            $tbl->foreign('booking_id')->references('id')->on('bookings');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('booking_seats', function ($tbl)
        {
            $tbl->dropColumn(['refundAmount', 'void']);
        });

        Schema::drop('refunds');
    }
}
