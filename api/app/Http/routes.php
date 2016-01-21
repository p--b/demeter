<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/

$app->get('/seatset', 'SeatsetController@getCurrent');
$app->delete('/seatset', 'SeatsetController@clear');

$app->put('/seatset/{performance}/{seat}', 'SeatsetController@addSeat');
$app->delete('/seatset/{performance}/{seat}', 'SeatsetController@removeSeat');

$app->get('/', function () use ($app) {
    return "1.0";
});

$app->get('/shows', 'ShowController@all');
$app->get('/shows/{id}', 'ShowController@show');

$app->get('/seatmaps/{id}', 'SeatmapController@getMap');
$app->get('/availability/{performance}', 'AvailabilityController@get');

$app->get('/booking/preview',    'BookingController@preview');
$app->post('/booking/completion', 'BookingController@complete');
