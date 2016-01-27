var Backbone = require('backbone');
var appState = require('../appState.js');

module.exports = {
    Totals: Backbone.Model.extend({
        urlRoot: appState.config.endpoint + 'booking/preview',
    }),
    Complete: Backbone.Model.extend({
        urlRoot: appState.config.endpoint + 'booking/completion',
    }),
};
