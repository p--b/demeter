var Backbone = require('backbone');
var appState = require('../appState.js');

var Map = Backbone.Model.extend({
    urlRoot: appState.config.endpoint + 'seatmaps',
});

module.exports = {
    Map: Map,
    MapSet: Backbone.Collection.extend({
        model: Map,
    }),
};
