<?php
    $menu = "";
    $req = $_SERVER['REQUEST_URI'];

    foreach (['/' => 'Book Tickets', '/about' => 'About &amp; Contact'] as $href => $name)
    {
        $sel   = $req == $href ? " class='selected'" : '';
        $menu .= "<menuitem><a href='$href' $sel>$name</a></menuitem>\n";
    }
?>
<!DOCTYPE html>
<html>
    <head>
        <meta charset="utf-8">
        <title>Imperial College Musical Theatre: Ticket Sales</title>
        <link rel="stylesheet" type="text/css" href="css/page.css">
        <link rel="stylesheet" type="text/css" href="css/demeter.css">
        <script src="https://checkout.stripe.com/checkout.js"></script>
    </head>
    <body>
        <header>
            <h1><span>Imperial College</span><span class="bar"></span>
                <span>Musical Theatre</span></h1>
            <h2>Online Ticketing</h2>
            <menu><?= $menu ?>
            </menu>
        </header>
        <div id="page">
