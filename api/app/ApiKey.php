<?php namespace Demeter;

use Illuminate\Database\Eloquent\Model;

class ApiKey extends Model
{
    public $timestamps = false;

    public function ticketStubs()
    {
        return $this->hasMany('Demeter\TicketStub');
    }
}
