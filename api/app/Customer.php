<?php namespace Demeter;

use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    public function bookings()
    {
        return $this->hasMany('Demeter\Booking');
    }
}
