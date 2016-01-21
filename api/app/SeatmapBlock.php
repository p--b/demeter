<?php namespace Demeter;

use Illuminate\Database\Eloquent\Model;

class SeatmapBlock extends Model
{
    public $timestamps = false;
    protected $table = 'blocks';

    public function seatmap()
    {
        return $this->belongsTo('Demeter\Seatmap');
    }

    public function rows()
    {
        return $this->hasMany('Demeter\BlockRow');
    }
}
