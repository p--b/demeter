<?php namespace Demeter;

use Illuminate\Database\Eloquent\Model;

class Refund extends Model
{
    public function booking()
    {
        return $this->belongsTo('Demeter\Booking');
    }
}
