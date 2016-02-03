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
        var parseDate = function(string) {
            var regex = /^(\d{4})-(\d{2})-(\d{2}) (\d{2}):(\d{2}):(\d{2})$/;
            var parts = regex.exec(string);
            return new Date(Date.UTC(parts[1], parts[2] -1, parts[3], parts[4], parts[5],parts[6]));
        };

        if ('performances' in data) {
            data.performances.forEach(function(performance) {
                performance.startsAt = parseDate(performance.startsAt);
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
