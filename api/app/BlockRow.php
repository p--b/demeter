<?php namespace Demeter;

use Illuminate\Database\Eloquent\Model;

class BlockRow extends Model
{
    public $timestamps = false;
    protected $table = 'rows';

    public function seatmapBlock()
    {
        return $this->belongsTo('Demeter\SeatmapBlock');
    }

    public function seats()
    {
        return $this->hasMany('Demeter\Seat');
    }
}
