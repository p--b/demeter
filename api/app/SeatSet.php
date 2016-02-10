<?php namespace Demeter;

use Illuminate\Database\Eloquent\Model;

class SeatSet extends Model
{
    public function booking()
    {
        return $this->belongsTo('Demeter\Booking');
    }

    public function seats()
    {
        return $this->hasMany('Demeter\BookingSeat');
    }

    public function getHeldSeats()
    {
        return $this->seats()->where('seat_held', 1)->get();
    }

    public function freezePrices($comp)
    {
        foreach ($this->getHeldSeats() as $seat)
            $seat->freezePrice($this, $comp);
    }
}
