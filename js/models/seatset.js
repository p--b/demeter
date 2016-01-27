var Backbone = require('backbone');
var appState = require('../appState.js');

var SeatData = Backbone.Model.extend({
    urlRoot: function()
    {
        return this.collection.url + '/' + this.collection.performance;
    },
});

var Seat = Backbone.Model.extend({
    urlRoot: function()
    {
        return appState.config.endpoint + 'seatset/' + this.performance;
    },
    initialize: function(opts)
    {
        this.performance = opts.performance;
    }
});

var SeatSet = Backbone.Collection.extend({
    model: SeatData,
    initialize: function(models, opts) {
        this.performance = opts.performance;
    },
    url: appState.config.endpoint + 'seatset',
    parse: function(data) {
        return data.seats;
    },
});

module.exports = {
    SeatSet: SeatSet,
    Seat: Seat,
}
