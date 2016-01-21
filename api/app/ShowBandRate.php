<?php namespace Demeter;

use Illuminate\Database\Eloquent\Model;

class ShowBandRate extends Model
{
    public $timestamps = false;

    public function band()
    {
        return $this->belongsTo('Demeter\Band');
    }

    public function rate()
    {
        return $this->belongsTo('Demeter\Rate');
    }
}
