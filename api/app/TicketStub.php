<?php namespace Demeter;

use Illuminate\Database\Eloquent\Model;
use DB;

class TicketStub extends Model
{
    public function apiKey()
    {
        return $this->belongsTo('Demeter\ApiKey');
    }

    public function performance()
    {
        return $this->belongsTo('Demeter\Performance');
    }

    public function seat()
    {
        return $this->belongsTo('Demeter\Seat');
    }

    public function bookingSeat()
    {
        DB::connection()->enableQueryLog();
        $seats = DB::table('booking_seats')
            ->where('booking_seats.seat_id', $this->seat_id)
            ->where('booking_seats.performance_id', $this->performance_id)
            ->join('bookings', 'bookings.seat_set_id',  '=', 'booking_seats.seat_set_id')
            ->where('bookings.id', $this->booking_id)
            ->first();
        error_log(print_r(DB::getQueryLog(), true));

        if (!$seats)
            throw new \Illuminate\Database\Eloquent\ModelNotFoundException;

        return $seats;
    }
}
