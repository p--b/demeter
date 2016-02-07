var React = require('react')
var _ = require('underscore');
var SeatSet = require('../models/seatset.js');
var BasketTimeout = require('./seatPicker.js').BasketTimeout;

var fmtCurrency = function(amount)
{
    return "£ " + (amount / 100).toFixed(2);
}

var Ratepicker = React.createClass({
    render: function() {
        var buttons = [];
        var ticketRate = this.props.ticket.get('rate');
        var that = this;
        var selRate = function(rateId) {
            var perfTix = new SeatSet.Seat({
                performance: this.props.ticket.get('performance'),
                id: this.props.ticket.attributes.id,
                rate: rateId,
            });
            perfTix.save().then(function() {
                that.props.ticket.collection.fetch();
            });
        };

        for (var rateId in this.props.rates) {
            if (!this.props.rates.hasOwnProperty(rateId))
                continue;

            buttons.push(<button key={rateId}
                                 className={rateId == ticketRate ? 'selected' : ''}
                                 onClick={selRate.bind(this, rateId)}>
                            {this.props.rates[rateId]}
                         </button>);
        }

        return <div className="buttonSet">
                    {buttons}
                </div>;
    }
});

var Bandviewer = React.createClass({
    render: function() {
        var band = this.props.bands[this.props.ticket.get('band')];
        return <p>{band}</p>;
    }
});

var PerfTix = React.createClass({
    render: function() {
        var perf = this.props.perf;
        var show = this.props.show;
        var basket = this.props.basket;

        if (!perf)
            return <p>Loading...</p>;

        var map = this.props.maps.get(perf.seat_map_id);

        if (!map) {
            return <p>Loading...</p>;
        }

        if (!map.get('bands')) {
            return <p>Loading...</p>;
        }

        var tixData = this.props.tix.map(function(t) {
            return <tr key={t.attributes.id}>
                        <td className="seatRef">{t.get('block')} {t.get('row')}{t.get('seatNumber')}</td>
                        <td className="band">
                            <Bandviewer ticket={t} bands={map.get('bands')} />
                        </td>
                        <td className="rate">
                            <Ratepicker ticket={t} rates={show.get('rates')} basket={basket} />
                        </td>
                        <td className="currency">{fmtCurrency(t.get('price'))}</td>
                    </tr>
        });

        return <div>
                    <h4><a href={"#/shows/" + show.id + '/' + perf.id}>
                        {perf.startsAt.toLocaleDateString()} @ {perf.startsAt.toLocaleTimeString()}
                    </a></h4>
                    <table>
                    <tbody>{tixData}
                    </tbody>
                    </table>
                    <a href={"#/shows/" + show.id + '/' + perf.id}>
                    Change seats for this performance
                    </a>
                </div>;
    }
});

var ShowTix = React.createClass({
    render: function() {
        var show = this.props.show;
        var tixByPerf = _.groupBy(this.props.basket, function(item) { return item.get('performance'); });
        var showPerfTix = [];
        var perfById = this.props.perfs;

        for (var perfId in tixByPerf) {
            if (!tixByPerf.hasOwnProperty(perfId))
                continue;

            if (!(perfId in perfById))
                return <p>Loading...</p>;

            var perf = perfById[perfId];
            showPerfTix.push(<PerfTix key={perfId}
                                      show={show}
                                      perf={perf}
                                      maps={this.props.maps}
                                      basket={this.props.basket}
                                      tix={tixByPerf[perfId]} />);
        }

        return <article className='basketshow'>
                    <h2>{show.get('fullName')}</h2>
                    <h3>{show.get('venue')}</h3>
                    {showPerfTix}
                </article>;
    }
});

var Totals = React.createClass({
    render: function() {
        return <table>
                    <tbody>
                        <tr><td><p>Subtotal:</p></td>
                            <td>{fmtCurrency(this.props.totals.get('net'))}</td></tr>
                        <tr><td><p title="The booking fee covers costs incurred in taking card payments.">
                                   Booking Fees:<sup className="tooltip">?</sup></p></td>
                            <td>{fmtCurrency(this.props.totals.get('fee'))}</td></tr>
                        <tr><td><p>Total:</p></td>
                            <td>{fmtCurrency(this.props.totals.get('gross'))}</td></tr>
                    </tbody>
                </table>
    }
});

var Stripe = React.createClass({
    render: function() {
        var buttonTxt = 'Proceed to Checkout ⇝';
        var props = this.props;
        var gross = this.props.totals.get('gross');
        var qty   = this.props.qty;

        var vend = function(e) {
            e.target.disabled = true;
            e.target.innerHTML = "Processing...";
            props.handler.open({
                name: 'ICU MTSoc Online Ticketing',
                description: '' + qty + ' Ticket' + ((qty == 1) ? '' : 's'),
                zipCode: true,
                billingAddress: true,
                currency: "gbp",
                amount: gross,
                closed: function() {
                    e.target.disabled = false;
                    e.target.innerHTML = buttonTxt;
                },
            });
        };
        return <button onClick={vend}>{buttonTxt}</button>
    }
});

var Outer = React.createClass({
    render: function() {
        var shows     = this.props.shows;
        var tixByShow = this.props.basket.groupBy('show');
        var showTixList = [];
        var qty         = this.props.basket.length;

        for (var showId in tixByShow) {
            if (!tixByShow.hasOwnProperty(showId))
                continue;

            var show = shows.get(showId);

            if (show) {
                showTixList.push(<ShowTix key={showId}
                                          show={shows.get(showId)}
                                          perfs={this.props.perfs}
                                          maps={this.props.maps}
                                          basket={tixByShow[showId]} />);
            } else {
                showTixList.push(<p key={showId}>Loading....</p>);
            }
        }

        var rubric = 'Please review the below details. Your tickets will be e-mailed to you after purchase.';

        var details = "All ticket sales are inclusive of V.A.T. at 20%.";

        if (this.props.error) {
            var error = <div className='error'>
                <h3>Sorry, there was a problem with your order</h3>
                <h4>Your card has not been charged.</h4>
                <p>{this.props.error}</p>
                <p>Please <strong>try again</strong>, or contact us at&nbsp;
                    <a href='mailto:musical@ic.ac.uk'>musical@ic.ac.uk</a> for
                    assistance.</p>
                </div>;
        } else {
            var error = null;
        }

        if (this.props.basket.created)
            var basketTimeout = <BasketTimeout basket={this.props.basket} />;
        else
            var basketTimeout = null;

        return <div><h1>Review your tickets</h1>
                <p>{rubric}</p>
                {error}
                {basketTimeout}
                <div className="reviewBox">
                {showTixList}
                <p className="boring">{details}</p>
                <div className="baskettotals plate">
                    <Totals totals={this.props.totals} />
                    <Stripe totals={this.props.totals}
                            qty={qty}
                            handler={this.props.handler} />
                </div></div></div>
    }
});

module.exports = {Outer: Outer};
