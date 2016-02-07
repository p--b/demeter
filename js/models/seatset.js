var Backbone = require('backbone');
var appState = require('../appState.js');
var parseDate = require('../appCommon.js').parseDate;

var SeatData = Backbone.Model.extend({
    idAttribute: "__nonexistent__",
    urlRoot: function()
    {
        return this.collection.url + '/' +
               this.collection.performance + '/' +
               this.attributes.id;
    },
    defaults: {
        id: '__invalid__',
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
        this.created = data.created_at ? parseDate(data.created_at) : null;

        return data.seats;
    },
    modelId: function(attrs) {
        return attrs.performance + ':' + attrs.id;
    }
});

module.exports = {
    SeatSet: SeatSet,
    Seat: Seat,
}
