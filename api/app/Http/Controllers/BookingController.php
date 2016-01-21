<?php

namespace Demeter\Http\Controllers;

use Demeter\Customer;
use Demeter\Booking;
use Demeter\BookingState;
use Demeter\BookingToken;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use DB;

class BookingController extends Controller
{
    public function complete(Request $r)
    {
        if (!($tok = $r->input('token')))
            return new Response(NULL, 400);

        try
        {
            $token = new BookingToken;
            $token->token = $tok;
            $token->disposition = BookingToken::FRESH;
            $token->save();
        }
        catch (\Illuminate\Database\QueryException $e)
        {
            // Integrity violation -> dupe token?
            if ($e->getCode(23000))
                return new Reponse(NULL, 410);

            throw $e;
        }

        $invalid = FALSE;

        DB::beginTransaction();
        $seatSet = app(SeatsetController::class)->getSS(FALSE);

        if (!$seatSet)
        {
            $token->disposition = BookingToken::NO_SS;
            $token->save();
            DB::commit();
            return new Response(NULL, 400);
        }

        try
        {
            DB::table('seat_sets')->where('id', $seatSet->id)->sharedLock()->get();
            // May have changed pending the lock, so let's refresh
            $seatSet->fresh();
        }
        catch (\Illuminate\Database\QueryException $e)
        {
            DB::commit();
            return new Response(NULL, 503);
        }

        if(!$seatSet->ephemeral || $seatSet->annulled)
        {
            $token->disposition = BookingToken::SS_INVALID;
            $token->save();
            DB::commit();
            return new Response(NULL, 409);
        }

        try
        {
            $cust = new Customer;
            $cust->email = $r->input('email');
            $cust->save();

            $booking = new Booking;
            $booking->customer()->associate($cust);
            $booking->name  = $r->input('name');
            $booking->state = BookingState::BOOKED;
            $booking->seatSet()->associate($seatSet);
            $booking->token()->associate($token);
            $booking->emailSent = FALSE;

            $totals = $this->determineCurrentTotals($seatSet);
            $booking->netValue = $totals['net'];
            $booking->grossValue = $totals['gross'];
            $booking->fees = $totals['fee'];
            $booking->save();

            $seatSet->ephemeral = FALSE;
            $seatSet->save();

            $token->disposition = BookingToken::READY;
            $token->save();
            DB::commit();
        }
        catch (\Illuminate\Database\QueryException $e)
        {
            // If something went wrong here, we probably had
            // bad/missing data.
            DB::rollback();
            $token->dispostion = BookingToken::BOOK_FAIL;
            $token->save();
            return new Response(NULL, 400);
        }

        $bookData = ['booking' => $booking->id];

        if ($this->takePayment($booking, $token, $declined, $e))
        {
            DB::transaction(function() use ($booking, $token)
            {
                $booking->state = BookingState::CONFIRMED;
                $token->disposition = BookingToken::CHARGED_OK;
                $booking->save();
                $token->save();
            });

            return $bookData;
        }
        else
        {
            DB::transaction(function() use ($booking, $token, $seatSet, $declined)
            {
                $booking->state     = BookingState::ABORTED;
                $token->disposition = $declined ? BookingToken::CHARGED_DECLINED
                                                : BookingToken::CHARGED_FAIL;
                $seatSet->ephemeral = TRUE;
                // TODO: Store charge exception
                $booking->save();
                $token->save();
                $seatSet->save();
            });

            return new Response($bookData, 402);
        }
    }

    public function preview()
    {
        $seatSet = app(SeatsetController::class)->getSS(FALSE);
        return response()->json($this->determineCurrentTotals($seatSet));
    }

    protected function takePayment($booking, $token, &$decline, &$exception)
    {
        try
        {
            // TODO: Is this enough info?
            $charge = \Stripe\Charge::create([
                'amount' => $booking->grossValue,
                'currency' => 'gbp',
                'source' => $token->token,
                'description' => 'Demeter#'.$booking->id,
            ]);
            return TRUE;
        }
        catch (\Stripe\Error\Card $e)
        {
            $exception = $e;
            $decline   = TRUE;
            return FALSE;
        }
        catch (\Exception $e)
        {
            $exception = $e;
            $declune   = FALSE;
            return FALSE;
        }
    }

    protected function determineCurrentTotals($seatSet)
    {
        $net = $this->determineCurrentNet($seatSet);
        $fee = $this->determineFee($net);
        $gross = $net + $fee;

        return ['net' => $net, 'fee' => $fee, 'gross' => $gross];
    }

    protected function determineCurrentNet($seatSet)
    {
        if (!$seatSet)
            return 0;

        $net = 0;

        foreach ($seatSet->getHeldSeats() as $seat)
            $net += $seat->getPrice();

        return $net;
    }

    protected function determineFee($net)
    {
        if ($net)
            return 40;

        return 0;
    }
}
