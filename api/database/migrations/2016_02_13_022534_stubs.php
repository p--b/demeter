<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class Stubs extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('api_keys', function ($tbl)
        {
            $tbl->increments('id');
            $tbl->string('key');
            $tbl->string('role');
            $tbl->unique('key');
        });

        Schema::table('booking_seats', function($tbl)
        {
            $tbl->index(['seat_id', 'performance_id', 'seat_set_id']);
        });

        Schema::create('ticket_stubs', function($tbl)
        {
            $tbl->integer('seat_id')->unsigned();
            $tbl->integer('seat_set_id')->unsigned();
            $tbl->integer('performance_id')->unsigned();
            $tbl->integer('booking_id')->unsigned();
            $tbl->integer('api_key_id')->unsigned();
            $tbl->boolean('initial')->default(TRUE)->nullable();
            $tbl->timestamps();
            $tbl->foreign('api_key_id')->references('id')->on('api_keys');
            $tbl->foreign('booking_id')->references('id')->on('bookings');
            $tbl->foreign(['seat_id', 'performance_id', 'seat_set_id'])
                ->references(['seat_id', 'performance_id', 'seat_set_id'])->on('booking_seats');
            $tbl->unique(['seat_id', 'performance_id', 'initial']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('ticket_stubs');
        Schema::dropIfExists('api_keys');

        Schema::table('booking_seats', function($tbl)
        {
            $tbl->dropIndex('booking_seats_seat_id_performance_id_seat_set_id_index');
        });
    }
}
