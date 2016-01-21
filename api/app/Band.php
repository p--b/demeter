<?php namespace Demeter;

use Illuminate\Database\Eloquent\Model;

class Band extends Model
{
    public $timestamps = false;

    public function seats()
    {
        return $this->hasMany('Demeter\Seat');
    }

    public function seatmap()
    {
        return $this->belongsTo('Demeter\Seatmap');
    }

    public function rates()
    {
        return $this->hasMany('Demeter\ShowBandRate');
    }
}
