<?php

namespace Demeter\Http\Controllers;

use Demeter\Customer;
use Demeter\Booking;
use Demeter\BookingState;
use Demeter\BookingToken;
use Demeter\TicketStub;
use Demeter\Console\Commands\DispatchCommand;

use Illuminate\Http\Response;
use Illuminate\Http\Request;
use DB;

class TicketController extends Controller
{
    protected $request;

    public function get(Request $request, $bookingId)
    {
        $this->request = $request;

        if (!$this->getAuth()->hasRole('get-ticket'))
            return new Response(NULL, 403);

        $booking = Booking::findOrFail($bookingId);

        if (!$booking->ticketsGenerated)
            $data = $this->generateTickets($booking);
        else
            $data = $this->getTicketData($bookingId);

        return (new Response($data))
            ->withHeaders(['Content-Type' => 'application/pdf']);
    }

    protected function generateTickets($booking)
    {
        // TODO: Refactor this functionality!
        $cmd = new DispatchCommand;
        return $cmd->generateTickets($booking,
                                     $booking->seatSet()->first(),
                                     $booking->customer()->first());
    }

    protected function getTicketData($bookingId)
    {
        return file_get_contents(storage_path()."/app/tickets-$bookingId.pdf");
    }

    protected function getAuth()
    {
        return $this->request->input('apiKey');
    }
}
