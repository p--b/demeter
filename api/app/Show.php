<?php namespace Demeter;

use Illuminate\Database\Eloquent\Model;

class Show extends Model
{
    public $timestamps = false;

    public function performances()
    {
        return $this->hasMany('Demeter\Performance');
    }

    public function rates()
    {
        return $this->hasMany('Demeter\Rate');
    }

    public function defaultRate()
    {
        return $this->hasOne('Demeter\ShowDefaultRate');
    }
}
