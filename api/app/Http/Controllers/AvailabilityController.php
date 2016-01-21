<?php

namespace Demeter\Http\Controllers;

use DB;
use Demeter\Performance;
use Demeter\BookingSeat;

class AvailabilityController extends Controller
{
    public function get($performance)
    {
        $perf = Performance::findOrFail($performance);
        return $this->getAvailability($perf);
    }

    public function getAvailability($perf)
    {
        $sets = DB::table('booking_seats')
            ->join('seat_sets', 'seat_sets.id', '=', 'booking_seats.seat_set_id')
            ->select('booking_seats.seat_id')
            ->where('seat_sets.annulled', 0)
            ->where('booking_seats.performance_id', $perf->id)
            ->where('booking_seats.seat_held', 1)
            ->lists('booking_seats.seat_id');

        return $sets;
    }
}
