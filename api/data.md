Demeter
=======

Shows
-----
* id
* name
* fullName
* venue

Performances
------------
* id
* show_id
* startsAt
* seatmap_id

Seatmaps
--------
* id

SeatmapBlocks
-------------
* id
* seatmapId
* name
* xOffset
* yOffset
* rotation

BlockRows
---------
* id
* blockId
* name
* rank

RowSeats
--------
* id
* rowId
* seatNum?
* bandId
* restricted

SeatBands
---------
* id
* seatmapId
* name

Rates
---------
* id
* name
* showId

BandRates
---------
* bandId
* rateId
* price

Customers
---------
* id
* email
* dateCreated

Bookings
--------
* id
* customerId
* name
* dateCreated
* state (booked, confirmed, held, refunded, aborted)
* netValue 
* fees
* grossValue
* emailSent
* seatSetId
* tokenId

SeatSets
--------
* id
* annulled
* dateCreated
* ephemeral

BookingSeats
------------
* seatSetId
* seatId
* performanceId
* rateId

BookingStripeTokens
-------------------
* id
* token
