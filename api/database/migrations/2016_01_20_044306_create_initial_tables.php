<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateInitialTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('shows', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->string('fullName');
            $table->string('venue');
        });

        Schema::create('seatmaps', function(Blueprint $tbl) {
            $tbl->increments('id');
            $tbl->timestamps();
        });

        Schema::create('bands', function(Blueprint $tbl) {
            $tbl->increments('id');
            $tbl->integer('seatmap_id')->unsigned();
            $tbl->string('name');

            $tbl->foreign('seatmap_id')->references('id')->on('seatmaps');
        });

        Schema::create('performances', function(Blueprint $table) {
            $table->increments('id');
            $table->integer('show_id')->unsigned();
            $table->dateTime('startsAt');
            $table->integer('seat_map_id')->unsigned();
            $table->boolean('available')->default(TRUE);

            $table->foreign('show_id')->references('id')->on('shows');
            $table->foreign('seat_map_id')->references('id')->on('seatmaps');
        });

        Schema::create('blocks', function(Blueprint $tbl) {
            $tbl->increments('id');
            $tbl->integer('seatmap_id')->unsigned();
            $tbl->string('name');
            $tbl->integer('xOffset')->default(0);
            $tbl->integer('yOffset')->default(0);
            $tbl->integer('rotation')->default(0);

            $tbl->foreign('seatmap_id')->references('id')->on('seatmaps');
        });

        Schema::create('rows', function(Blueprint $tbl) {
            $tbl->increments('id');
            $tbl->integer('seatmap_block_id')->unsigned();
            $tbl->string('name');
            $tbl->integer('rank')->default(0);

            $tbl->foreign('seatmap_block_id')->references('id')->on('blocks');
        });

        Schema::create('seats', function(Blueprint $tbl) {
            $tbl->increments('id');
            $tbl->integer('block_row_id')->unsigned();
            $tbl->integer('seatNum')->nullable();
            $tbl->integer('band_id')->unsigned();
            $tbl->boolean('restricted');

            $tbl->foreign('block_row_id')->references('id')->on('rows');
            $tbl->foreign('band_id')->references('id')->on('bands');
        });

        Schema::create('rates', function(Blueprint $tbl) {
            $tbl->increments('id');
            $tbl->integer('show_id')->unsigned();
            $tbl->string('name');

            $tbl->foreign('show_id')->references('id')->on('shows');
        });

        Schema::create('show_default_rates', function (Blueprint $tbl) {
            $tbl->integer('show_id')->unsigned();
            $tbl->integer('rate_id')->unsigned();

            $tbl->primary(['show_id', 'rate_id']);
            $tbl->foreign('show_id')->references('id')->on('shows');
            $tbl->foreign('rate_id')->references('id')->on('rates');
        });

        Schema::create('show_band_rates', function(Blueprint $tbl) {
            $tbl->integer('band_id')->unsigned();
            $tbl->integer('rate_id')->unsigned();
            $tbl->integer('price')->unsigned();

            $tbl->primary(['band_id', 'rate_id']);
            $tbl->foreign('band_id')->references('id')->on('bands');
            $tbl->foreign('rate_id')->references('id')->on('rates');
        });

        Schema::create('customers', function(Blueprint $tbl) {
            $tbl->increments('id');
            $tbl->timestamps();
            $tbl->string('email');
        });

        Schema::create('seat_sets', function(Blueprint $tbl) {
            $tbl->increments('id');
            $tbl->timestamps();
            $tbl->boolean('annulled')->default(FALSE);
            $tbl->boolean('ephemeral')->default(TRUE);
        });

        Schema::create('bookings', function(Blueprint $tbl) {
            $tbl->increments('id');
            $tbl->integer('customer_id')->unsigned();
            $tbl->timestamps();
            $tbl->string('name');
            $tbl->string('state');
            $tbl->integer('net')->unsigned();
            $tbl->integer('fees')->unsigned();
            $tbl->integer('gross')->unsigned();
            $tbl->boolean('ticketsSent')->default(FALSE);
            $tbl->integer('seat_set_id')->unsigned();
            $tbl->integer('token_id')->unsigned();

            $tbl->foreign('customer_id')->references('id')->on('customers');
            $tbl->foreign('seat_set_id')->references('id')->on('seat_sets');
            $tbl->foreign('token_id')->references('id')->on('tokens');
        });

        Schema::create('booking_seats', function(Blueprint $tbl) {
            $tbl->integer('seat_set_id')->unsigned();
            $tbl->boolean('seat_held')->default(TRUE)->nullable();
            $tbl->integer('seat_id')->unsigned();
            $tbl->integer('performance_id')->unsigned();
            $tbl->integer('rate_id')->unsigned();

            $tbl->primary(['seat_set_id', 'seat_id', 'performance_id']);
            $tbl->foreign('seat_set_id')->references('id')->on('seat_sets');
            $tbl->foreign('seat_id')->references('id')->on('seats');
            $tbl->foreign('performance_id')->references('id')->on('performances');
            $tbl->foreign('rate_id')->references('id')->on('rates');
            $tbl->unique(['seat_held', 'seat_id', 'performance_id']);
        });

        Schema::create('booking_tokens', function(Blueprint $tbl) {
            $tbl->increments('id');
            $tbl->string('token');
            $tbl->string('disposition');
            $tbl->unique('token');
        });

        // TODO: Model for this
        Schema::create("charge_exceptions", function(Blueprint $tbl) {
            $tbl->integer('booking_token_id')->unsigned();
            $tbl->string('exception');
            $tbl->foreign('booking_token_id')->references('id')->on('booking_tokens');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('show_default_rates');
        Schema::drop('charge_exceptions');
        Schema::drop('booking_tokens');
        Schema::drop('booking_seats');
        Schema::drop('bookings');
        Schema::drop('seat_sets');
        Schema::drop('customers');
        Schema::drop('show_band_rates');
        Schema::drop('rates');
        Schema::drop('seats');
        Schema::drop('rows');
        Schema::drop('blocks');
        Schema::drop('performances');
        Schema::drop('bands');
        Schema::drop('seatmaps');
        Schema::drop('shows');
    }
}
