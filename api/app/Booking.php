<?php namespace Demeter;

use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
    public function customer()
    {
        return $this->hasOne('Demeter\Customer');
    }

    public function seatSet()
    {
        return $this->hasOne('Demeter\SeatSet');
    }

    public function token()
    {
        return $this->belongsTo('Demeter\BookingToken');
    }
}
