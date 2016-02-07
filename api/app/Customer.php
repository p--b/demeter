<?php namespace Demeter;

use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    public function bookings()
    {
        return $this->hasMany('Demeter\Booking');
    }

    public function getAddress()
    {
        return [$this->addrLine,
                $this->addrCity,
                $this->addrPostcode,
                $this->addrCountry];
    }
}
