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

    public function getRefData()
    {
        $row = $this->blockRow()->first();
        $block = $row->seatmapBlock()->first();

        return [
            'num' => $this->seatNum,
            'row' => $row->name,
            'block' => $block->name,
            'band' => $this->band()->first()->name,
        ];
    }
}
