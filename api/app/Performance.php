<?php namespace Demeter;

use Illuminate\Database\Eloquent\Model;

class Performance extends Model
{
    public $timestamps = false;

    public function show()
    {
        return $this->belongsTo('Demeter\Show');
    }

    public function seatMap()
    {
        return $this->belongsTo('Demeter\Seatmap');
    }
}
