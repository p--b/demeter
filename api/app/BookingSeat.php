<?php namespace Demeter;

use Illuminate\Database\Eloquent\Model;
use DB;

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

    public function freezePrice($seatSet = NULL, $comp = FALSE)
    {
        if ($seatSet == NULL)
            $seatSet = $this->seatSet()->first();

        $price = $comp ? 0 : $this->getPrice();

        DB::table('booking_seats')
                ->where('seat_set_id', $seatSet->id)
                ->where('seat_id', $this->seat_id)
                ->update(['pricePaid' => $price]);
    }
}
