<?php

namespace Demeter\Console\Commands;

use Illuminate\Console\Command;
use Demeter\Booking;
use Demeter\Refund;

use DB;

class RefundCommand extends Command {
    protected $name = 'demeter:refund';
    protected $description = "Marks a given booking reference as refunded, and all its tickets as void.";
    protected $signature = 'demeter:refund {bookingId}';

    public function fire()
    {
        DB::connection()->enableQueryLog();
        DB::transaction(function() {
            $booking = Booking::findOrFail($this->argument('bookingId'));
            $refunds = $booking->refunds()->lists('id');

            if (count($refunds))
            {
                $nos = implode(', ', $refunds->toArray());
                throw new \RuntimeException("Not re-processing; refunds already exist [$nos]");
            }

            $sSet = $booking->seatSet()->first();
            $sSet->annulled = TRUE;
            $sSet->save();

            foreach ($sSet->getHeldSeats() as $seat)
            {
                DB::table('booking_seats')
                        ->where('seat_set_id', $sSet->id)
                        ->where('seat_id', $seat->seat_id)
                        ->update(['refundAmount' => $seat->pricePaid,
                                  'void'         => TRUE,
                                   'seat_held'   => FALSE]);
            }

            $refund = new Refund;
            $refund->booking()->associate($booking);
            $refund->net   = $booking->net;
            $refund->fees  = $booking->fees;
            $refund->gross = $booking->gross;
            $refund->save();
        });
        print_r(DB::getQueryLog());
    }
}
