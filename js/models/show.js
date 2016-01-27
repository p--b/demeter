var Backbone = require('backbone');
var appState = require('../appState.js');

var Show = Backbone.Model.extend({
    defaults: {
        id: null,
        name: null,
        fullName: null,
        venue: null,
    },
    urlRoot: function() {
        return appState.config.endpoint + 'shows';
    },
    parse: function(data) {
        if ('performances' in data) {
            data.performances.forEach(function(performance) {
                performance.startsAt = new Date(performance.startsAt);
            });
        }

        return data;
    }
});

var Shows = Backbone.Collection.extend({
    model: Show,
    url: appState.config.endpoint + 'shows',
})

module.exports = {
    Show: Show,
    Shows: Shows,
}
