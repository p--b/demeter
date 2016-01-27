var React    = require('react');
var ReactDOM = require('react-dom');
var Backbone = require('backbone');

var appState = require('./appState.js');

var ShowController = require('./controllers/show.js');
var SeatController = require('./controllers/seat.js');

var RouterCxtor = Backbone.Router.extend({
    "routes": {
        "shows/:id": "show",
        "shows/:showId/:performanceId": "performance",
        "confirm": "confirm",
        "done": "done",
        "*any": "default",
    }
});

var AppRouter = new RouterCxtor();
AppRouter.on('route:default', ShowController.list);
AppRouter.on('route:show', ShowController.choosePerformance);
AppRouter.on('route:performance', SeatController.pick);
AppRouter.on('route:confirm', SeatController.confirm);
AppRouter.on('route:done', SeatController.done);

Backbone.history.start();
