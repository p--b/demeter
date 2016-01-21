<?php namespace Demeter;

use Illuminate\Database\Eloquent\Model;

class Seat extends Model
{
    public $timestamps = false;

    public function blockRow ()
    {
        return $this->belongsTo('Demeter\BlockRow');
    }

    public function band()
    {
        return $this->belongsTo('Demeter\Band');
    }
}
