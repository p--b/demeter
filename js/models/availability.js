var Backbone = require('backbone');
var appState = require('../appState.js');

module.exports = Backbone.Model.extend({
    urlRoot: appState.config.endpoint + 'availability',
    parse: function(data)
    {
        return {
            takenSeats: data
        }
    }
});
