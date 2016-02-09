var React     = require('react');
var Backbone  = require('backbone');
var appCommon = require('../appCommon.js');

module.exports = React.createClass({
    render: function() {
        var contentsByPerf = this.props.contents.groupBy('performance');
        var ticketList     = [];

        for (var perfId in contentsByPerf)
        {
            if (!contentsByPerf.hasOwnProperty(perfId))
                continue;

            // Sometimes perfId will be undefined, and will defy
            // all attempts to be tested for. Really make sure
            // we do have an integral ID before attempting to
            // display performance information!
            if (Number.isNaN(parseInt(perfId)))
                continue;

            if (perfId in this.props.performances) {
                var perf = this.props.performances[perfId];
                var pdate = perf.startsAt;
                var perfInfo = <h2>
                {pdate.toLocaleDateString()}
                &nbsp;@&nbsp;{appCommon.formatTime(pdate)}
                </h2>;
            } else {
                var perfInfo = <h2>For another show:</h2>;
            }

            perfTix = contentsByPerf[perfId].map(function(item)
            {
                if (!item.attributes.id)
                    return null;

                return <li key={item.attributes.id + '-' + item.get('performance')}>
                    <span className="seatName">{item.get('block')} {item.get('row')}{item.get('seatNumber')}</span>
                </li>;
            }).filter(function(v) { return v != null; });

            if (perfTix.length)
                ticketList.push(<div key={perfId}>{perfInfo}<ul>{perfTix}</ul></div>);
        }

        if (!ticketList.length)
            ticketList = null;

        var checkout = function() {
            Backbone.history.navigate('/confirm', {trigger: true});
        };

        var checkoutBtn =
            <button
               className='checkout btn'
               disabled={!ticketList}
               onClick={checkout}>
               Buy Tickets &#8669;
            </button>;

        var empty = <span className='empty'>Your basket is empty!</span>;

        return <article className="basket">
            <h1>Your basket</h1>
            {ticketList || empty}
            {checkoutBtn}
        </article>;
    }
});
