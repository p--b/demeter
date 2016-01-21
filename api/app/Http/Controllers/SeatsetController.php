<?php

namespace Demeter\Http\Controllers;

use Demeter\SeatSet;
use Demeter\BookingSeat;
use Demeter\Performance;
use Demeter\Seat;
use Demeter\Rate;

use DB;

use Illuminate\Http\Request;
use Illuminate\Http\Response;

class SeatsetController extends Controller
{
    public function findCurrent()
    {
        $ss = $this->getSS(FALSE);

        if (NULL == $ss)
            return [];

        $ssData = $ss->jsonSerialize();
        $ssData['seats'] = [];
        
        foreach ($ss->getHeldSeats() as $bookingSeat)
        {
            $seat = $bookingSeat->seat()->first();
            $row  = $seat->blockRow()->first();

            $seatData = [
                'id'         => $seat->id,
                'seatNumber' => $seat->seatNum,
                'rate'       => $bookingSeat->rate_id,
                'band'       => $seat->band_id,
                'price'      => $bookingSeat->getPrice(),
                'row'        => $row->id,
                'block'      => $row->seatmapBlock()->first()->id,
            ];

            $ssData['seats'][] = $seatData;
        }

        return $ssData;
    }

    public function getCurrent()
    {
        return response()->json($this->findCurrent());
    }

    public function clear()
    {
        $ss = $this->getSS();
        BookingSeat::where('seat_set_id', $ss->id)->delete();
        $ss->delete();

        return new Response(NULL, 204);
    }

    public function addSeat(Request $r, $performance, $seat)
    {
        $ss = $this->getSS();
        $prfModel  = Performance::findOrFail($performance);
        $seatModel = Seat::findOrFail($seat);
        $rateModel = Rate::findOrFail($r->input('rate'));

        // Check show, map compatibility
        $prfMap   = $prfModel->seatMap()->first();
        $seatMap  = $seatModel->blockRow()->first()->seatmapBlock()->first()->seatMap()->first();
        $rateShow = $rateModel->show()->first();
        $prfShow  = $prfModel->show()->first();

        if ($prfMap->id != $seatMap->id || $rateShow->id != $prfShow->id)
            return new Response(NULL, 400);

        // If we already have a booking for this seat, update it
        if (DB::table('booking_seats')
            ->select('*')
            ->where('seat_set_id', $ss->id)
            ->where('seat_id', $seatModel->id)
            ->where('performance_id', $prfModel->id)
            ->count())
        {
            return $this->updateBookingSeat($ss, $prfModel, $seatModel, $rateModel);
        }

        // Else create a new one
        $bookSeat = new BookingSeat;
        $bookSeat->performance()->associate($prfModel);
        $bookSeat->seat()->associate($seatModel);
        $bookSeat->rate()->associate($rateModel);
        $bookSeat->seatSet()->associate($ss);

        return $this->catchIntegrityViolation(function() use ($bookSeat)
        {
            $bookSeat->save();
            return new Response(NULL, 204);
        });
    }

    public function updateBookingSeat($set, $performance, $seat, $rate)
    {
        return $this->catchIntegrityViolation(function() use ($set, $performance, $seat, $rate)
        {
            DB::table('booking_seats')
            ->where('seat_set_id', $set->id)
            ->where('seat_id', $seat->id)
            ->where('performance_id', $performance->id)
            ->update(['rate_id' => $rate->id,
                      'seat_held' => 1]);

            return new Response(NULL, 204);
        });
    }

    public function removeSeat($performance, $seat)
    {
        $ss = $this->getSS();
        BookingSeat::where('seat_set_id', $ss->id)
            ->where('performance_id', $performance)
            ->where('seat_id', $seat)
            ->delete();

        if (0 === BookingSeat::where('seat_set_id', $ss->id)->count())
            $ss->delete();

        return new Response(NULL, 204);
    }

    public function getSS($create = TRUE)
    {
        $this->init();
        $ss = SeatSet::findOrNew($this->getSSId());

        if ($extant = $ss->exists)
        {
            if ($ss->annulled || !$ss->ephemeral)
            {
                $extant = FALSE;
                $ss = new SeatSet;
            }
        }

        if (!$extant)
        {
            if ($create)
            {
                $ss->save();
                $_SESSION['ssid'] = $ss->id;
            }
            else
            {
                return NULL;
            }
        }

        return $ss;
    }

    protected function catchIntegrityViolation($c)
    {
        try
        {
            return $c();
        }
        catch (\Illuminate\Database\QueryException $e)
        {
            // 23000 is SQLSTATE of integrity violation
            // UNIQUE on the db enforces no double-booking
            if ($e->getCode() == 23000)
                return new Response(NULL, 409);

            throw $e;
        }
    }

    protected function init()
    {
        session_start();
    }

    protected function getSSId()
    {
        if (isset($_SESSION['ssid']))
            return $_SESSION['ssid'];

        return NULL;
    }
}
