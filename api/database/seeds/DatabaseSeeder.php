<?php

use Illuminate\Database\Seeder;

use Demeter\Show;
use Demeter\Seatmap;
use Demeter\Performance;
use Demeter\SeatmapBlock;
use Demeter\BlockRow;
use Demeter\Seat;
use Demeter\Rate;
use Demeter\ShowBandRate;
use Demeter\Band;

class DatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $s = new Show;
        $s->name = 'oklahoma';
        $s->fullName = 'ICU MTSoc Presents: Oklahoma!';
        $s->venue = 'Hammersmith Apollo';
        $s->save();

        $rates = [];
        foreach (['Adult', 'Concession', 'VIP'] as $rname)
        {
            $rate = new Rate;
            $rate->name = $rname;
            $rate->show()->associate($s);
            $rate->save();

            $rates[$rname] = $rate;
        }

        $sm = new Seatmap;
        $sm->save();

        $dt = new \DateTime;
        $performances = [];

        for ($i = 3; $i--;)
        {
            $performances[] = ($p = new Performance);
            $p->show()->associate($s);
            $p->seatMap()->associate($sm);
            $p->startsAt = clone $dt;
            $p->save();
            $dt->add(new \DateInterval("P1D"));
        }

        $bands = [];

        foreach (['Premium', 'Standard', 'Restricted'] as $name)
        {
            $bnd = new Band;
            $bnd->seatmap()->associate($sm);
            $bnd->name = $name;
            $bnd->save();
            $bands[$name] = $bnd;
        }

        foreach (['stalls', 'circle'] as $name)
        {
            $blk = new SeatmapBlock;
            $blk->seatmap()->associate($sm);
            $blk->name = $name;
            $blk->xOffset = 4;
            $blk->yOffset = 7;
            $blk->rotation = 0;
            $blk->save();

            for ($i = 10; $i--;)
            {
                $row = new BlockRow;
                $row->seatmapBlock()->associate($blk);
                $row->name = chr(ord('A') + $i);
                $row->rank = $i;
                $row->save();

                if ($i == 9)
                    $seatBnd = $bands['Restricted'];
                else if ($i < 3)
                    $seatBnd = $bands['Premium'];
                else
                    $seatBnd = $bands['Standard'];

                for ($j = 10; $j--;)
                {
                    $seat = new Seat;
                    $seat->blockRow()->associate($row);
                    $seat->seatNum = $j;
                    $seat->band()->associate($seatBnd);
                    $seat->restricted = $i == 9;
                    $seat->save();
                }
            }
        }

        foreach ($bands as $band)
        {
            foreach ($rates as $rate)
            {
                $sbr = new ShowBandRate;
                $sbr->band()->associate($band);
                $sbr->rate()->associate($rate);
                $sbr->price = 100 * rand(1, 20);
                $sbr->save();
            }
        }
    }
}
