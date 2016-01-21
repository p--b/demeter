<?php namespace Demeter;

use Illuminate\Database\Eloquent\Model;

class Seatmap extends Model
{
    public function blocks()
    {
        return $this->hasMany('Demeter\SeatmapBlock');
    }

    public function bands()
    {
        return $this->hasMany('Demeter\Band');
    }
}
