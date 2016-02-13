<?php

namespace Demeter\Http\Controllers;

use Demeter\Customer;
use Demeter\Booking;
use Demeter\BookingState;
use Demeter\BookingToken;
use Demeter\TicketStub;

use Illuminate\Http\Response;
use Illuminate\Http\Request;
use DB;

class StubController extends Controller
{
    protected $request;

    public function take(Request $request, $performance, $seat)
    {
        $this->request = $request;

        if (!$this->getAuth()->hasRole('stub-take'))
            return new Response(NULL, 403);

        if (!($booking = $request->input('booking')))
            return new Response(NULL, 400);

        try
        {
            $stub = $this->addStub($performance, $seat, $booking);
            $seat = $stub->bookingSeat();

            if (!$seat->seat_held)
                return new Response(NULL, 402);

            if ($seat->void)
                return new Response(NULL, 410);

            return new Response(NULL, 204);
        }
        catch (\Illuminate\Database\QueryException $e)
        {
            if ($e->getCode() != 23000)
                throw $e;

            // Integrity violation -> doesn't exist, or is duplicate.
            try
            {
                $this->addStub($performance, $seat, $booking, TRUE);
                return new Response(NULL, 409);
            }
            catch (\Illuminate\DatabaseQueryException $e)
            {
                if ($e->getCode() != 23000)
                    throw $e;

                // If we still can't insert it, the FOREIGN to
                // the booking_seats must have failed. No such seat booked.
                return new Response(NULL, 404);
            }
        }
    }

    protected function addStub($performance, $seat, $booking, $allowDupe = FALSE)
    {
        $stub = new TicketStub;
        $stub->performance_id = $performance;
        $stub->seat_id        = $seat;
        $stub->booking_id     = $booking;
        $stub->api_key_id     = $this->getAuth()->getId();

        if ($allowDupe)
            $stub->initial = NULL;

        $stub->save();
        return $stub;
    }

    protected function getAuth()
    {
        return $this->request->input('apiKey');
    }
}
