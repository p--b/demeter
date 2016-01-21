<?php namespace Demeter;

use Illuminate\Database\Eloquent\Model;

class Rate extends Model
{
    public $timestamps = false;

    public function show()
    {
        return $this->belongsTo('Demeter\Show');
    }
}
