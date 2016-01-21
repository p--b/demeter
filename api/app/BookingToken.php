<?php namespace Demeter;

use Illuminate\Database\Eloquent\Model;

class BookingToken extends Model
{
    public $timestamps = false;

    const FRESH = 'fresh';
    const BOOK_FAIL  = 'book_fail';
    const NO_SS      = 'no_ss';
    const SS_INVALID = 'ss_invalid';
    const CHARGED_OK = 'charged_ok';
    const CHARGED_FAIL = 'charged_fail';
    const CHARGED_DECLINED = 'charged_declined';

    public function booking()
    {
        return $this->hasOne('Demeter\Booking');
    }
}
