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
            return new Response('no tok', 400);

        if (!($src = $r->input('source')))
            return new Response('no src', 400);

        try
        {
            $token = new BookingToken;
            $token->token = $tok;
            $token->disposition = BookingToken::FRESH;
            $token->source = BookingToken::classify($src);
            $token->validateToken();
            $token->save();
        }
        catch (\Illuminate\Database\QueryException $e)
        {
            // Integrity violation -> dupe token?
            if ($e->getCode() == 23000)
                return new Response('duplicate token', 410);

            throw $e;
        }
        catch (\Demeter\BookingTokenException $e)
        {
            return new Response('Bad token', 400);
        }

        $comp     = $src == BookingToken::TYPE_COMP;
        $doStripe = $src == BookingToken::TYPE_STRIPE;
        $invalid  = FALSE;

        DB::beginTransaction();
        $seatSet = app(SeatsetController::class)->getSS(FALSE);

        if (!$seatSet)
        {
            $token->disposition = BookingToken::NO_SS;
            $token->save();
            DB::commit();
            return new Response('No SS', 404);
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
            return new Response('no lock', 503);
        }

        if(!$seatSet->ephemeral || $seatSet->annulled)
        {
            $token->disposition = BookingToken::SS_INVALID;
            $token->save();
            DB::commit();
            return new Response('SS Invalid', 409);
        }

        try
        {
            $cust = new Customer;
            foreach (['email' => 'email',
                      'pymtAddrLine1' => 'addrLine',
                      'pymtAddrCity' => 'addrCity',
                      'pymtAddrZip' => 'addrPostcode',
                      'pymtAddrCountry' => 'addrCountry'] as $input => $field)
                $cust->$field = $r->input($input);

            $cust->save();

            $booking = new Booking;
            $booking->name  = $r->input('name');
            $booking->state = BookingState::BOOKED;
            $booking->token()->associate($token);
            $booking->customer()->associate($cust);
            $booking->seatSet()->associate($seatSet);

            $totals = $this->determineCurrentTotals($seatSet, $comp, $doStripe);
            $booking->net = $totals['net'];
            $booking->gross = $totals['gross'];
            $booking->fees = $totals['fee'];
            $booking->save();

            $seatSet->ephemeral = FALSE;
            $seatSet->freezePrices($comp);
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
            $token->disposition = BookingToken::BOOK_FAIL;
            $token->save();
            error_log($e);
            return new Response('sql bounce', 400);
        }

        $bookData = ['booking' => $booking->id];

        if (!$doStripe || $this->takePayment($booking, $token, $declined, $e))
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
            DB::transaction(function() use ($booking, $token, $seatSet, $declined, $e)
            {
                $booking->state     = BookingState::ABORTED;
                $token->disposition = $declined ? BookingToken::CHARGED_DECLINED
                                                : BookingToken::CHARGED_FAIL;
                $seatSet->ephemeral = TRUE;
                error_log($e);
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
            $this->configureStripe();
            $charge = \Stripe\Charge::create([
                'amount'               => $booking->gross,
                'currency'             => 'gbp',
                'source'               => $token->token,
                'description'          => 'Booking ID #'.$booking->id,
                'statement_descriptor' => 'ICU Online Tickets',
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
            $decline   = FALSE;
            return FALSE;
        }
    }

    protected function configureStripe()
    {
        \Stripe\Stripe::setApiKey($_ENV['STRIPE_SKEY']);
    }

    protected function determineCurrentTotals($seatSet,
                                              $comp = FALSE,
                                              $feeCharged = TRUE)
    {
        if ($comp)
            return ['net' => 0, 'fee' => 0, 'gross' => 0];

        $net = $this->determineCurrentNet($seatSet);
        $fee = $feeCharged ? $this->determineFee($net) : 0;
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
        if (!$net)
            return 0;

        $const = 20;
        $rate  = 0.014;
        $tax   = 0.2;

        $shouldCharge = (($net * (1 - $tax) + $const) /
                         (1 - $tax - $rate)           ) - $net;

        return ceil($shouldCharge);
    }
}
