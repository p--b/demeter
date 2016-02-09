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
use Demeter\ShowDefaultRate;

class SweeneySeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $s = new Show;
        $s->name = 'Sweeney Todd';
        $s->fullName = 'Sweeney Todd: The Demon Barber of Fleet Street';
        $s->venue = 'ICU Concert Hall';
        $s->description = 'Sweeney Todd: The Demon Barber of Fleet Street is a 1979 musical thriller with music and lyrics by Stephen Sondheim and book by Hugh Wheeler. The musical details the return of barber Sweeney Todd to London after 15 years of exile, in order to take revenge on the corrupt judge who banished him, by conspiring with a local baker, Mrs. Lovett, who is in desperate need of fresh meat for her pies.';
        $s->save();

        $rates = [];
        $first = TRUE;
        $defaultId = NULL;
        foreach (['Adult', 'Concession'] as $rname)
        {
            $rate = new Rate;
            $rate->name = $rname;
            $rate->show()->associate($s);
            $rate->save();

            if ($first)
            {
                $first = FALSE;
                $defaultId = $rate;
            }

            $rates[$rname] = $rate;
        }

        $defaultRate = new ShowDefaultRate;
        $defaultRate->show()->associate($s);
        $defaultRate->rate()->associate($defaultId);
        $defaultRate->save();

        $sm = new Seatmap;
        $sm->save();

        $dt = new \DateTime('2016-03-02 19:30:00');
        $clone = function() use ($dt) { return clone $dt; };
        $perfTimes = [
            [$dt, 20],
            [$clone()->add(new \DateInterval('P1D')), 20],
            [$clone()->add(new \DateInterval('P1DT23H')), 10], // Fri 1830
            [$clone()->add(new \DateInterval('P2DT2H30M')), 10], // Fri 2200
            [new \DateTime('2016-03-05 14:30:00'), 20],
            [$clone()->add(new \DateInterval('P3D')), 20], // Sat late
        ];
        $performances = [];

        foreach ($perfTimes as $time)
        {
            $doorsTime = clone $time[0];
            $doorsTime->sub(new \DateInterval("PT{$time[1]}M"));
            $doorsTimeStr = $doorsTime->format('G:i');
            $performances[] = ($p = new Performance);
            $p->show()->associate($s);
            $p->seatMap()->associate($sm);
            $p->startsAt = $time[0];
            $p->description = "Doors open {$time[1]} minutes before the performance starts.";
            $p->ticketNote = "Doors at {$doorsTimeStr}";
            $p->save();
        }

        $bands = [];

        foreach (['Standard', 'Restricted View'] as $name)
        {
            $bnd = new Band;
            $bnd->seatmap()->associate($sm);
            $bnd->name = $name;
            $bnd->save();
            $bands[$name] = $bnd;
        }

        $makeBlock = function($name, $coords, $rotation, $block) use ($sm)
        {
            $blk = new SeatmapBlock;
            $blk->seatmap()->associate($sm);
            $blk->name = $name;
            $blk->xOffset = $coords[0];
            $blk->yOffset = $coords[1];
            $blk->rotation = $rotation;
            $blk->save();

            $block($blk);
        };

        $makeRows = function($block, $rowSpec, $startAt, $inc = TRUE, $firstRow = 'A', $rowFix = NULL) use ($bands)
        {
            $last = ord($firstRow) + count($rowSpec) - 1;
            $startAt--;

            foreach ($rowSpec as $i => $rowcount)
            {
                $rowStart = $startAt;
                $row = new BlockRow;
                $row->seatmapBlock()->associate($block);
                $row->name = chr($last - $i);
                $row->rank = $i;
                $row->leftAlign = $inc;
                $seatFix = NULL;
                if ($rowFix && ($newStart = $rowFix($i, $row, $seatFix)))
                    $rowStart = $newStart - 1;

                $row->save();

                $band = $bands['Standard'];

                for ($j = 1; $j <= $rowcount; $j++)
                {
                    $seat = new Seat;
                    $seat->blockRow()->associate($row);
                    $seat->seatNum = $inc ? $j + $rowStart : 1 + $rowStart + $rowcount - $j;
                    $seat->band()->associate($band);
                    $seat->restricted = FALSE;
                    if ($seatFix)
                        $seatFix($j, $seat);
                    $seat->save();
                }
            }
        };

        $makeBlock('Stalls L', [5, 0], 0, function($blk) use ($makeRows)
        {
            $makeRows($blk, [8, 8, 8, 8, 8, 4], 1, TRUE, 'A', function($i, $row, &$seatFix)
            {
                if ($i == 5)
                {
                    $row->leftAlign = FALSE;
                    return 5;
                }
                else if ($i == 0)
                {
                    $seatFix = function($i, $seat)
                    {
                        $seat->hidden = TRUE;
                    };
                }
            });
        });

        $makeBlock('Stalls R', [25, 0], 0, function($blk) use ($makeRows)
        {
            $makeRows($blk, [8, 8, 8, 8, 8, 4], 9);
        });

        $makeBlock('Promenade L', [-10, 28], -90, function($blk) use ($makeRows)
        {
            $makeRows($blk, [15, 11, 10], 1, false);
        });

        $makeBlock('Promenade R', [31, 22], 90, function($blk) use ($makeRows, $bands)
        {
            $makeRows($blk, [11, 11, 10], 1, true, 'D');
            $rstBand = $bands['Restricted View']->id;
            foreach (['D' => 7, 'E' => 6, 'F' => 2] as $row => $seat)
                $blk->rows()->where('name', $row)->first()
                    ->seats()->where('seatNum', '>=', $seat)
                    ->update(['restricted' => TRUE,
                              'band_id' => $rstBand]);
        });

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
