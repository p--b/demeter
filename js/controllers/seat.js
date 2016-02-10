var React = require('react');
var Backbone = require('backbone');
var ReactDOM = require('react-dom');
var appState = require('../appState.js');
var _        = require('underscore');

var Basket = require('../components/basket.js');
var Seats  = require('../components/seatPicker.js');
var Confirm = require('../components/confirmationScreen.js');
var CompletionScreen = require('../components/completionScreen.js');

var SeatSet = require('../models/seatset.js');
var ShowModel = require('../models/show.js');
var SeatMap = require('../models/seatmap.js');
var Availability = require('../models/availability.js');
var Booking = require('../models/booking.js');

module.exports = {
    pick: function(show, performance) {
        var basket = new SeatSet.SeatSet([], {performance: performance});
        var performances = {};

        basket.on('change sync', function()
        {
            ReactDOM.render(<Basket contents={basket}
                                    performances={performances} />,
                            appState.Viewport.getElementsByClassName('basketCnt')[0]);
        });

        var showMdl = new ShowModel.Show({id: show});
        var seatmap = null;
        var availability = new Availability({id: performance});
        var defRate = null;
        var getBasket = function()
        {
            basket.fetch();
            availability.fetch();
        };

        var onSeatSelected = function(seatData) {
            if (seatData.selected) {
                var model = basket.get(performance + ':' + seatData.id);
                model.sync('delete', model, {success: getBasket});
            } else {
                basket.create({id: seatData.id, rate: defRate},
                              {success: getBasket,
                               type: 'put'});
            }
        };

        availability.fetch();

        showMdl.on('sync change', function(sData)
        {
            defRate = sData.get('defaultRate');
            var perfs = sData.get('performances');
            var perfObj = null;

            for (var i = 0; i < perfs.length; i++)
            {
                performances[perfs[i].id] = perfs[i];

                if (perfs[i].id == performance)
                    perfObj = perfs[i];
            }

            basket.fetch();

            if (perfObj == null)
                console.error("Couldn't find performance!");

            seatmap = new SeatMap.Map({id: perfObj.seat_map_id});
            var updateSeats = function() {
                ReactDOM.render(<Seats.SeatPicker
                                    show={sData}
                                    perf={perfObj}
                                    availability={availability}
                                    basket={basket}
                                    onSeatSel={onSeatSelected}
                                    orphanCheck={appState.config.orphanCheck}
                                    seatmap={seatmap} />,
                                appState.Viewport.getElementsByClassName('seatmapCnt')[0]);
            };

            seatmap.on('sync change', updateSeats);
            availability.on('sync change', updateSeats);
            basket.on('sync change', updateSeats);
            seatmap.fetch();
        });

        showMdl.fetch();

        ReactDOM.render(
            <div>
                <div className="basketCnt"></div>
                <div className="seatmapCnt"></div>
            </div>,
        appState.Viewport);
    },
    confirm: function() {
        var smap = appState.Viewport.getElementsByClassName('seatmapCnt');

        if (smap.length)
            ReactDOM.unmountComponentAtNode(smap[0]);

        var basket = new SeatSet.SeatSet([], {performance: null});
        var totals = new Booking.Totals();
        var shows  = new ShowModel.Shows();
        var maps   = new SeatMap.MapSet();
        var perfs  = {};

        var checkoutComplete = function(token) {
            appState.token = token;
            Backbone.history.navigate('done', {trigger: true});
        };

        var handler = StripeCheckout.configure({
            key: appState.config.StripeKey,
            locale: 'auto',
            token: checkoutComplete,
        });

        if ('checkoutError' in appState) {
            var error = appState.checkoutError;
            appState.checkoutError = null;
        } else {
            var error = null;
        }

        var updateShows = function() {
            if (!error && (!basket.length || basket.models[0].attributes.id == null))
                Backbone.history.navigate('', {trigger: true});

            var baskByShow = basket.groupBy('show');

            for (var showId in baskByShow) {
                if (!baskByShow.hasOwnProperty(showId))
                    continue;

                if (!shows.get(showId)) {
                    var show = new ShowModel.Show({id: showId});
                    show.fetch().then(function() {
                        shows.add(show);
                    });
                }
            }
        };

        var updateRates = function() {
            var baskByPerf = basket.groupBy('performance');

            var lookupShowPerf = function(perfId) {
                var showId = baskByPerf[perfId][0].get('show');
                var showPerfs = shows.get(showId).get('performances');
                var perf = _.groupBy(showPerfs, 'id')[perfId][0];

                perfs[perfId] = perf;
            };

            for (var perfId in baskByPerf) {
                if (!baskByPerf.hasOwnProperty(perfId))
                    continue;

                if (!(perfId in perfs))
                    lookupShowPerf(perfId);

                if (!maps.get(perfs[perfId].seat_map_id)) {
                    var map = new SeatMap.Map({id: perfs[perfId].seat_map_id});
                    map.fetch().then(function() {
                        maps.add(map);
                    });
                }
            }
        };

        var redraw = function()
        {
            ReactDOM.render(
                <Confirm.Outer error={error}
                               basket={basket}
                               totals={totals}
                               handler={handler}
                               perfs={perfs}
                               maps={maps}
                               shows={shows} />,
            appState.Viewport);
        };

        basket.on("sync change", function() {totals.fetch();});
        basket.on("sync change", redraw);
        basket.on("sync change", updateShows);
        totals.on("sync change", redraw);
        shows.on("change", redraw);
        shows.on("add", updateRates);
        maps.on("add change", redraw);
        basket.fetch();
    },
    done: function() {
        if (!appState.token) {
            Backbone.history.navigate('confirm', {trigger: true});
            return;
        }

        ReactDOM.render(
            <CompletionScreen.working />,
        appState.Viewport);

        var handleError = function(e)
        {
            console.log(e);
            if ('statusCode' in e)
            {
                var classify = function(e)
                {
                    switch (e.status) {
                        case 400: return "Your booking request appeared to be faulty.";
                        case 402: return "We failed to take payment from your card. The card may be expired, invalid or declined. Please try again, or try another card.";
                        case 409: return "Your seats have been invalidated as you took longer than 15 minutes to book. Please select new seats and try again.";
                        case 500: return "Our booking system experienced an internal problem.";
                        case 503: return "A conflict was detected when trying to secure your booking. Please close any other browser windows open on this site.";
                        default: return  "Something went wrong which we can't identify, sorry.";
                    }
                };

                appState.checkoutError = classify(e);
            }
            else
            {
                appState.checkoutError = "We're not sure what went wrong, sorry.";
            }

            Backbone.history.navigate('confirm', {trigger: true});
        };

        try {
            var token = appState.token;
            var bk = new Booking.Complete({
                name: token.card.name,
                email: token.email,
                token: token.id,
                source: 'stripe',
                pymtAddrLine1: token.card.address_line1,
                pymtAddrZip: token.card.address_zip,
                pymtAddrState: token.card.address_state,
                pymtAddrCity: token.card.address_city,
                pymtAddrCountry: token.card.address_country,
            });

            bk.save({}, {
                success: function(model) {
                    ReactDOM.render(
                        <CompletionScreen.done />,
                    appState.Viewport);
                },
                error: function(model, response) {
                    console.log("API error saving booking.complete");
                    console.log(response);
                    console.log(model);
                    handleError(response);
                }
            });
        } catch (e) {
            console.log("Exception saving booking.complete");
            handleError(e);
        }
    }
}
