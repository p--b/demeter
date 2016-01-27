<?php

namespace Demeter\Http\Controllers;

use Demeter\Show;
use Demeter\Performance;
use Demeter\ShowBandRate;

class ShowController extends Controller
{
    public function all()
    {
        $shows = Show::all();

        return response()->json($shows);
    }

    public function show($id)
    {
        $show     = Show::findOrFail($id);
        $showData = $show->jsonSerialize();
        $rates    = $show->rates()->get();

        $showData['performances'] = $this->getPerformanceData($show, $rates);
        $showData['rates'] = [];

        foreach ($rates as $rate)
            $showData['rates'][$rate->id] = $rate->name;

        $showData['defaultRate'] = $show->defaultRate()->first()->rate()->first()->id;

        return response()->json($showData);
    }

    protected function getPerformanceData($show, $rates)
    {
        $performances = $show->performances();
        $pData = $performances->get()->jsonSerialize();

        foreach ($pData as &$perf)
        {
            $bands = Performance::findOrFail($perf['id'])->seatMap()->first()->bands()->get();

            foreach ($rates as $rate)
            {
                foreach ($bands as $band)
                {
                    $sbr = ShowBandRate::where('band_id', $band->id)->where('rate_id', $rate->id)->first();
                    $perf['prices'][$rate->id][$band->id] = $sbr->price;
                }
            }
        }

        return $pData;
    }
}
