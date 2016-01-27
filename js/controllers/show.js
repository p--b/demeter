var React = require('react');
var ReactDOM = require('react-dom');
var appState = require('../appState.js');

var ShowModel = require('../models/show.js');

var ShowList = require('../components/showList.js');
var PerformanceList = require('../components/performanceList.js');

module.exports = {
    list: function() {
        var shows = new ShowModel.Shows();

        shows.on('sync change', function()
        {
            ReactDOM.render(
                <ShowList shows={shows} />,
                appState.Viewport);
        });

        shows.fetch();
    },
    choosePerformance: function(show) {
        var showModel = new ShowModel.Show({id: show});
        showModel.on('sync change', function(sData)
        {
            ReactDOM.render(
                    <PerformanceList show={sData} />,
                    appState.Viewport);
        });

        showModel.fetch(); 
    },
};
