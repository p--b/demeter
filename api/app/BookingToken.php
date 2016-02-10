<?php namespace Demeter;

use Illuminate\Database\Eloquent\Model;

class BookingToken extends Model
{
    public $timestamps = false;

    const FRESH            = 'fresh';
    const BOOK_FAIL        = 'book_fail';
    const NO_SS            = 'no_ss';
    const SS_INVALID       = 'ss_invalid';
    const READY            = 'ready';
    const CHARGED_OK       = 'charged_ok';
    const CHARGED_FAIL     = 'charged_fail';
    const CHARGED_DECLINED = 'charged_declined';

    const TYPE_STRIPE = 'stripe';
    const TYPE_COMP   = 'comp';
    const TYPE_CASH   = 'cash';

    public function booking()
    {
        return $this->hasOne('Demeter\Booking');
    }

    public function validateToken()
    {
        switch ($this->source)
        {
        case self::TYPE_STRIPE:
            if ('tok_' == substr($this->token, 0, 4))
                return TRUE;
            break;

        case self::TYPE_COMP:
            $secret = $_ENV['TOK_SECRET_COMP'];
            goto join_COMPCASH;

        case self::TYPE_CASH:
            $secret = $_ENV['TOK_SECRET_CASH'];

        join_COMPCASH:
            if (FALSE === $sepPos = strpos($this->token, ':'))
                break;

            $inputSecret = substr($this->token, 0, $sepPos);

            if (hash_equals($secret, $inputSecret))
                return TRUE;
            break;

        default:
            break;
        }

        throw new BookingTokenException("Invalid token");
    }

    public function describeSource()
    {
        switch ($this->source)
        {
        case self::TYPE_STRIPE: return 'Card';
        case self::TYPE_CASH:   return 'Cash';
        case self::TYPE_COMP:   return '* COMP *';
        default:                return '* UNKNOWN *';
        }
    }

    public static function classify($typeString)
    {
        $types = [
            self::TYPE_STRIPE,
            self::TYPE_COMP,
            self::TYPE_CASH
        ];

        if (in_array($typeString, $types, TRUE))
            return $typeString;

        throw new BookingTokenException("Invalid token type");
    }
}
