var Backbone = require('backbone');
var appState = require('../appState.js');

module.exports = {
    Totals: Backbone.Model.extend({
        urlRoot: appState.config.endpoint + 'booking/preview',
    }),
    Complete: Backbone.Model.extend({
        urlRoot: appState.config.endpoint + 'booking/completion',
    }),
    Stub: Backbone.Model.extend({
        initialize: function(data, opts) {
            this.performance = opts.performance;
            this.seat        = opts.seat;
        },
        urlRoot: function() {
            return appState.config.endpoint + 'stubs/'
                    + this.performance + '/'
                    + this.seat;
        }
    }),
};
