var React    = require('react');
var ReactDOM = require('react-dom');
var Backbone = require('backbone');

var appState = require('./appState.js');

var ShowController = require('./controllers/show.js');
var SeatController = require('./controllers/seat.js');
var OpsController = require('./controllers/operations.js');

var RouterCxtor = Backbone.Router.extend({
    "routes": {
        "shows/:id": "show",
        "shows/:showId/:performanceId": "performance",
        "confirm": "confirm",
        "done": "done",
        "ops/entry/:showId/:performanceId": "entry",
        "ops/box": "box",
        "*any": "default",
    }
});

var AppRouter = new RouterCxtor();
AppRouter.on('route:default', ShowController.list);
AppRouter.on('route:show', ShowController.choosePerformance);
AppRouter.on('route:performance', SeatController.pick);
AppRouter.on('route:confirm', SeatController.confirm);
AppRouter.on('route:entry', OpsController.doEntry);
AppRouter.on('route:box', OpsController.doBox);
AppRouter.on('route:done', SeatController.done);

Backbone.history.start();
