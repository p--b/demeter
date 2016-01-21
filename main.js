// main.js
var React = require('react');
var ReactDOM = require('react-dom');
var s = require('./components/seat.js')
var data = {Y: [{id: 1, number: 4, selected: true},
                {id: 5, number: 42},
               {id: 7, number: 5, taken: true}],
            A: [{id: 9, number: 34, restricted: true},
                {id: 6, number: 34, restricted: true, taken: true},
                {id: 4, number: 35, restricted: true, selected: true}]};

var render = function() {
    ReactDOM.render(
        <div className="arena">
            <s.Block name="Stalls" rows={data} />
            <s.Block name="Stalls2" rows={data} rotate='90'
                                    left="200px" top="300px" />
        </div>,
      document.getElementById('content')
    );
}


render();
