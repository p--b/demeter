<?php

namespace Demeter\Http\Controllers;

use Demeter\Show;
use Demeter\Performance;
use Demeter\Seatmap;

class SeatmapController extends Controller
{
    public function getMap($id)
    {
        $map    = Seatmap::findOrFail($id);
        $blocks = $map->blocks()->get();
        $bands  = $map->bands()->get();
        $mData  = ['id' => $id, 'bands' => [], 'blocks' => []];

        foreach ($bands as $band)
            $mData['bands'][$band->id] = $band->name;

        foreach ($blocks as $block)
        {
            $bData = &$mData['blocks'][$block->id];
            $bData = [
                'name'     => $block->name,
                'offset'   => [$block->xOffset, $block->yOffset],
                'rotation' => $block->rotation,
                'rows'     => [],
            ];

            foreach ($block->rows()->get() as $row)
            {
                $rData = &$bData['rows'][$row->id];
                $rData = [
                    'name'  => $row->name,
                    'seats' => [],
                ];

                foreach ($row->seats()->get() as $seat)
                    $rData['seats'][$seat->id] = $seat;
            }
        }

        return response()->json($mData);
    }
}
