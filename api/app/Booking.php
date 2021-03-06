<?php namespace Demeter;

use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
    public function customer()
    {
        return $this->belongsTo('Demeter\Customer');
    }

    public function seatSet()
    {
        return $this->belongsTo('Demeter\SeatSet');
    }

    public function token()
    {
        return $this->belongsTo('Demeter\BookingToken');
    }

    public function refunds()
    {
        return $this->hasMany('Demeter\Refund');
    }
}
