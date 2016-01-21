<?php namespace Demeter;

use Illuminate\Database\Eloquent\Model;

class BookingSeat extends Model
{
    public $timestamps = false;

    public function seatSet()
    {
        return $this->belongsTo('Demeter\SeatSet');
    }

    public function seat()
    {
        return $this->belongsTo('Demeter\Seat');
    }

    public function performance()
    {
        return $this->belongsTo('Demeter\Performance');
    }

    public function rate()
    {
        return $this->belongsTo('Demeter\Rate');
    }

    public function getPrice()
    {
        $rate = $this->rate()->first();
        $band = $this->seat()->first()->band()->first();

        return ShowBandRate::where('band_id', $band->id)
                           ->where('rate_id', $rate->id)
                           ->first()->price;
    }
}
