<?php namespace Demeter;

use Illuminate\Database\Eloquent\Model;

class ShowDefaultRate extends Model
{
    public $timestamps = false;

    public function show()
    {
        return $this->belongsTo('Demeter\Show');
    }

    public function rate()
    {
        return $this->belongsTo('Demeter\Rate');
    }
}
