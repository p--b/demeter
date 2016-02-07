<?php

namespace Demeter\Console\Commands;

use Illuminate\Console\Command;

use Demeter\Booking;
use Demeter\BookingState;
use Demeter\Performance;

use DB;

class DispatchCommand extends Command {
    protected $name = 'demeter:dispatch';
    protected $description = "Dispatches tickets to customers.";

    public function fire()
    {
        $toDispatch = Booking::where('state', BookingState::CONFIRMED)
                        ->where('ticketsSent', 0)
                        ->get();

        $xport = \Swift_MailTransport::newInstance();
        $mailer = \Swift_Mailer::newInstance($xport);

        foreach ($toDispatch as $booking)
        {
            $customer = $booking->customer()->first();
            $seatSet  = $booking->seatSet()->first();
            $this->info("Dispatching #$booking->id to $booking->name: $customer->email");
            $mail = $this->getTicketMail($this->generateTickets($booking, $seatSet, $customer),
                                         $booking,
                                         $customer);
            $mailer->send($mail);
            $booking->ticketsSent = 1;
            $booking->save();
        }

        $n = count($toDispatch);
        $this->info("Dispatched $n bookings.");
    }

    protected function generateTickets($booking, $seatSet, $customer)
    {
        $facBuild = \PHPPdf\Core\FacadeBuilder::create(); // WHY CANNOT I JUST new?!?!
        $fac = $facBuild->build();
        $tixPdf = $fac->render($this->getTix($booking, $seatSet, $customer), $this->getTixSheet());
        file_put_contents(storage_path()."/app/tickets-$booking->id.pdf", $tixPdf);

        return $tixPdf;
    }

    protected function getTicketMail($ticketFile, $booking, $customer)
    {
        $attach = \Swift_Attachment::newInstance()
                    ->setFilename('tickets-'.$booking->id.'.pdf')
                    ->setContentType('application/pdf')
                    ->setBody($ticketFile);

        $msg = \Swift_Message::newInstance()
                ->setSubject('MTSoc Online Ticket Purchase: Tickets Attached')
                ->setFrom(['musical@imperial.ac.uk' => 'ICU MTSoc Ticketing System'])
                ->setTo([$customer->email => $booking->name])
                ->setBody($this->getTextEmail($booking, $customer), 'text/plain')
                ->attach($attach);

        return $msg;
    }

    protected function getTextEmail($booking, $customer)
    {
        $total = number_format($booking->gross / 100, 2);
        return <<<EOF
Dear $booking->name,

Thank-you for using the MTSoc Online Ticketing System. Your tickets are attached.

** Please be sure to print off your tickets and bring them with you to the performance.**

Booking details:
================
Booking Reference: $booking->id
Date: $booking->created_at
Total Paid: £$total

If you have any problems, please contact us by replying to this e-mail,
or at musical@imperial.ac.uk

Thank-you for using the MTSoc Online Ticketing System!

Kind regards,

Imperial College Musical Theatre Society
EOF;
    }

    protected function getTix($booking, $seatSet, $customer)
    {
        $tixPages = [];
        $tixData  = [];
        $numFmt = function($pence)
        {
            return '£ '.number_format($pence / 100, 2);
        };

        $getPerf = function($id)
        {
            static $perfs = [];

            if (isset($perfs[$id]))
                return $perfs[$id];

            $perf = Performance::findOrFail($id);

            return $perfs[$id] = [
                'perf' => $perf,
                'show' => $perf->show()->first(),
            ];
        };

        $seats    = $seatSet->seats()->get();
        $numSeats = count($seats);
        foreach ($seats as $seatId => $seat)
        {
            $seatNum = $seatId + 1;
            $sData   = $getPerf($seat->performance_id);
            $rate    = $seat->rate()->first()->name;
            $smSeat  = $seat->seat()->first();
            $seatRef = $smSeat->getRefData();
            $price   = $numFmt($seat->pricePaid);

            foreach (['perf', 'show'] as $name)
                $$name = $sData[$name];

            $barcode   = $this->calcBarcode($booking, $seat, $seatRef, $perf, $show);
            $datetime  = date('Y-m-d H:i:s');
            $tixData[] = [$show->fullName.'<br />'.$perf->startsAt,
                          "{$seatRef['block']} {$seatRef['row']}{$seatRef['num']}",
                          $smSeat->band()->first()->name,
                          $rate,
                          $numFmt($seat->pricePaid / 1.2),
                          $price];

            $tixPages[] = <<<EOF
<page>
    <div class="ticket">
            <h1>$show->fullName</h1>
            <h2>$show->venue</h2>
            <h3>$perf->startsAt</h3>
            <div class="seatData">
                <span class="block"> {$seatRef['block']} </span>
                <span class="seat">{$seatRef['row']}{$seatRef['num']}</span>
            </div>
            <p class="rate">Admit one <span class="rateName"> $rate </span></p>
            <p class="price">$price</p>
            <p class="customer">$booking->name #$booking->id :: Ticket {$seatNum} / $numSeats
             :: generated at $datetime</p>
            <barcode type="code128" code="$barcode" with-checksum="1" />
    </div>
</page>
EOF;
        }

        $receiptRows = [];

        foreach ($tixData as $tix)
            $receiptRows[] = '<td class="wide">'.implode('</td><td>', $tix).'</td>';

        $receiptRowStr = '<tr>'.implode('</tr><tr>', $receiptRows).'</tr>';
        $tixpageStr = implode($tixPages);
        $custAddr = implode('<br />', $customer->getAddress());
        return <<<EOF
<pdf>
    <dynamic-page>
        <placeholders>
            <footer><div class="pages">Receipt page <page-info format="%s / %s"/></div></footer>
        </placeholders>
        <h1>MTSoc Online Ticketing System</h1>
        <h2>Receipt</h2>
        <p>Thank-you for purchasing tickets online!</p>
        <p><strong>This VAT receipt is not a ticket.</strong></p>
        <p><span class="def">Sold by:</span> Imperial College Union, Prince Consort Road, London, SW7 2BB.
            VAT registration #: GB 240 5617 84</p>
        <table class="info">
            <tr><td>Invoice to:</td><td><strong>$booking->name</strong><br />$custAddr</td></tr>
            <tr><td>Booking reference / Invoice #:</td><td>$booking->id</td></tr>
            <tr><td>Booking date:</td><td>$booking->created_at</td></tr>
        </table>
        <table>
            <tr><td>Performance</td><td>Seat</td><td>Band</td><td>Rate</td><td>Price ex. VAT</td><td>Price inc. VAT</td></tr>
            $receiptRowStr
        </table>
        <table class="totals">
            <tr><td>Subtotal:</td><td>{$numFmt($booking->net)}</td></tr>
            <tr><td>Booking Fee:</td><td>{$numFmt($booking->fees)}</td></tr>
            <tr><td>Total Paid:</td><td>{$numFmt($booking->gross)}</td></tr>
        </table>
        <p>Prices include VAT charged at 20%.</p>
        <table class="vat">
            <tr><td>Total ex. VAT:</td><td>{$numFmt($booking->gross / 1.2)}</td></tr>
            <tr><td>VAT at 20%:</td><td>{$numFmt($booking->gross / 6)}</td></tr>
            <tr><td>Total inc. VAT:</td><td>{$numFmt($booking->gross)}</td></tr>
        </table>
    </dynamic-page>
    $tixpageStr
</pdf>
EOF;
    }

    protected function getTixSheet()
    {
        return <<<EOF
<stylesheet>
    <dynamic-page font-type="DejaVuSans">
    </dynamic-page>
    <div class="pages" height="25px">
        <complex-attribute name="border" color="black" size="1px" style="solid" type="bottom" />
    </div>
    <page font-type="DejaVuSans">
    </page>
    <h2>
        <complex-attribute name="border" color="black" size="1px" style="solid" type="bottom" />
    </h2>
    <div class="ticket" padding="10px" position="relative">
        <complex-attribute name="border" color="black" size="1px" style="solid" />
    </div>
    <div class="seatData" font-size="30pt" float="right">
        <span class="block" background.color="black" color="white">
        </span>
    </div>
    <p class="rate" font-size="13pt" margin-top="-35px" margin-left="40px">
        <span class="rateName" font-size="15pt">
            <complex-attribute name="border" color="black" size="1px" style="solid" />
        </span>
    </p>
    <p class="price" font-size="15pt" padding-right="10px"
        top="60mm" width="100%" text-align="right">
    </p>
    <td padding="5px"></td>
    <td class="wide" width="200%"></td>
    <table margin-top="10px">
    </table>
    <table class="info" width="60%">
    </table>
    <table class="totals" width="40%" float="right">
    </table>
    <table class="vat" width="40%" float="right">
    </table>
    <span class="def" font-style="bold"></span>
</stylesheet>
EOF;
    }

    protected function calcBarcode($booking, $seat, $seatRef, $perf, $show)
    {
        $data = implode('|', [
            0,
            $booking->id,
            $show->id,
            $perf->id,
            $seat->seat_id,
            $seatRef['block'],
            $seatRef['row'],
            $seatRef['num']]);
        $hash      = hash_hmac($_ENV['TIX_HMAC_ALGO'], $data, $_ENV['TIX_HMAC_KEY']);
        $not       = "<>&\"'";
        $targetSet = preg_replace("/[$not]/", "", implode(range('!', '~')));
        return "$data@".$this->rebase($this->bchexdec($hash), $targetSet);
    }

    protected function rebase($decimal, $targetSet)
    {
        $base = strlen($targetSet);
        $new  = '';

        while ($decimal > 0)
        {
            $index = bcmod($decimal, $base);
            $new .= $targetSet[$index];
            $decimal = bcsub($decimal, $index);
            $decimal = bcdiv($decimal, $base);
        }

        return $new;
    }

    protected function bchexdec($hex)
    {
        $return = 0;
        $hexLen = strlen($hex);
        $hexAlphabet = '0123456789abcdef';

        for ($i = 1; $i < $hexLen; $i++)
            $return = bcadd($return,
                            bcmul(strpos($hexAlphabet, $hex[$i - 1]),
                                bcpow(16, $hexLen - $i)));

        return $return;
    }
}
