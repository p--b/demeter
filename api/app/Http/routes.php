<?php

$app->get('/seatset', 'SeatsetController@getCurrent');
$app->delete('/seatset', 'SeatsetController@clear');

$app->put('/seatset/{performance}/{seat}', 'SeatsetController@addSeat');
$app->delete('/seatset/{performance}/{seat}', 'SeatsetController@removeSeat');

$app->get('/', function () {
    return "1.0";
});

$app->get('/shows', 'ShowController@all');
$app->get('/shows/{id}', 'ShowController@show');

$app->get('/seatmaps/{id}', 'SeatmapController@getMap');
$app->get('/availability/{performance}', 'AvailabilityController@get');

$app->get('/booking/preview',    'BookingController@preview');
$app->post('/booking/completion', 'BookingController@complete');

$app->post('/stubs/{performance}/{seat}', ['uses' => 'StubController@take',
                                           'middleware' => 'auth']);
$app->get('/tickets/{bookingId}', ['uses' => 'TicketController@get',
                                   'middleware' => 'auth']);
