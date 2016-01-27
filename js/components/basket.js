var React = require('react');
var Backbone = require('backbone');

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
                {pdate.toLocaleDateString()} @ {pdate.toLocaleTimeString()}
                </h2>;
            } else {
                console.log(this.props.performances);
                console.log(perfId);
                var perfInfo = <h2>For another show:</h2>;
            }

            perfTix = contentsByPerf[perfId].map(function(item)
            {
                if (!item.id)
                    return null;

                return <li key={item.id}>
                    <span className="seatName">{item.get('block')} {item.get('row')}{item.get('seatNumber')}</span>
                </li>;
            }).filter(function(v) { return v != null; });

            if (perfTix.length)
                ticketList.push(<div key={perfId}>{perfInfo}<ul>{perfTix}</ul></div>);
        }

        if (!ticketList.length)
            ticketList = null;

        var checkout = function()
        {
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
