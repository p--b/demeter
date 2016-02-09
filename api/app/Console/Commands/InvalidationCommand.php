<?php

namespace Demeter\Console\Commands;

use Illuminate\Console\Command;

use DB;

class InvalidationCommand extends Command {
    protected $name = 'demeter:invalidate';
    protected $description = "Invalidates expired ephemeral seat-sets.";
    protected $signature = 'demeter:invalidate {--c|camp}';

    public function fire()
    {
        if ($this->option('camp'))
        {
            $this->info("Camping for 1 minute; will snipe every 10 seconds.");

            $this->doExpiry();
            for ($i = 0; $i < 5; $i++)
            {
                sleep(10);
                $this->doExpiry();
            }
        }
        else
        {
            $this->doExpiry();
        }
    }

    public function doExpiry()
    {
        $db = $_ENV['DB_CONNECTION'];
        $expiryMins = $_ENV['EXPIRY_MINS'];

        if ($db == 'sqlite')
            $dateTerm = 'datetime(created_at, "+" || ? || " minutes") < datetime("now")';
        else if ($db == 'mysql')
            $dateTerm = 'DATE_ADD(created_at, INTERVAL ? MINUTE) < now()';
        else
            throw new \RuntimeException("Unknown database type $db");

        DB::transaction(function() use ($dateTerm, $expiryMins)
        {
            $expired = DB::table('seat_sets')
                            ->where('ephemeral', 1)
                            ->where('annulled', 0)
                            ->whereRaw($dateTerm, [$expiryMins])
                            ->lists('id');

            $expiredQty = DB::table('seat_sets')
                            ->whereIn('id', $expired)
                            ->update(['annulled' => 1]);

            $this->info("Expired $expiredQty seat sets.");

            $released = DB::table('booking_seats')
                            ->whereIn('seat_set_id', $expired)
                            ->update(['seat_held' => NULL]);

            $this->info("Released $released seats.");
        });
    }
}
