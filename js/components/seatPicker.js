var React     = require('react')
var appState  = require('../appState.js');
var appCommon = require('../appCommon.js');

var Seat = React.createClass({
    render: function() {
        var props = this.props
        var seatSelect = function(e) {
            var list = e.target.classList;
            if (!list.contains('loading')) {
                if (false != props.onSeatSel(props)) {
                    if (!list.contains('taken') || list.contains('selected'))
                        list.add('loading');
                }
            }
        }
        var classes = 'seat'

        if (this.props.selected == 1) {
            classes += ' selected'
        }

        if (this.props.restricted == 1) {
            classes += ' restricted'
        }

        if (this.props.taken == 1) {
            classes += ' taken'
        }

        if (this.props.hidden == 1) {
            classes += ' hidden'
        }

        return <div className={classes}
                    style={this.props.style}
                    onClick={seatSelect}>
            {this.props.number}
        </div>
    }
});

var Row = React.createClass({
    render: function() {
        var style = {}
        var that = this;

        var selected = function(seatProps) {
            if (that.props.orphanCheck && !seatProps.selected) {
                var pre = 0;
                var preLongest = 0;
                var post = 0;
                var postLongest = 0;
                var postDist = 0;
                var doingPre = true;
                var doingPost = false;

                var orphan = function() {
                    window.alert("You may not leave an isolated seat unoccupied in a row!" +
                        " Please choose a seat next to your existing selection.");
                };

                var avail = function(id) {
                    return !(id in that.props.avail && that.props.avail[id]);
                };

                for (var seatId in that.props.seats) {
                    if (doingPre) {
                        if (seatProps.id == seatId) {
                            doingPre = false;
                            doingPost = true;

                            if (pre > preLongest)
                                preLongest = pre;
                        } else if (avail(seatId)) {
                            pre++;
                        } else {
                            if (pre > preLongest)
                                preLongest = pre;

                            pre = 0;
                        }
                    } else {
                        if (avail(seatId)) {
                            post++;
                        } else {
                            if (post > postLongest)
                                postLongest = post;

                            if (doingPost) {
                                postDist = post;
                                doingPost = false;
                            }

                            post = 0;
                        }
                    }
                }

                if (post > postLongest)
                    postLongest = post;

                if (doingPost)
                    postDist = post;

                var test = function(count) {
                    if (count == 1) {
                        if (preLongest > count || postLongest > count) {
                            orphan();
                            return true;
                        }
                    }

                    return false;
                };

                if (test(pre) || test(postDist))
                    return false;
            }

            that.props.onSeatSel(seatProps);
        };

        if (this.props.seatRotate) {
            style.transform = 'rotate(' + this.props.seatRotate + 'deg)';
        }

        var rowClass = 'row';

        if (this.props.leftAlign == false)
            rowClass += ' right';

        var rowSeats = [];

        for (var seatId in this.props.seats)
        {
            if (!this.props.seats.hasOwnProperty(seatId))
                continue;

            seat = this.props.seats[seatId];
            rowSeats.push(<Seat key={seatId}
                         onSeatSel={selected}
                         style={style}
                         id={seatId}
                         number={seat.seatNum}
                         restricted={seat.restricted}
                         hidden={seat.hidden}
                         taken={seatId in this.props.avail}
                         selected={seatId in this.props.mine} />);
        }

        return <div className={rowClass}>
        <span className="rowName">{this.props.name}</span>
        {rowSeats}
        </div>
    }
});

var Block = React.createClass({
    render: function() {
        var blockRows  = []
        var style      = {}
        var seatRotate = false

        if (this.props.left) {
            style.left = this.props.left + 'em';
        }

        if (this.props.top) {
            style.top = this.props.top + 'em';
        }

        if (this.props.rotate) {
            style.transform = 'rotate(' + this.props.rotate + 'deg)';
            seatRotate      = -this.props.rotate;
        }

        for (var rowId in this.props.rows) {
            if (this.props.rows.hasOwnProperty(rowId)) {
                row = this.props.rows[rowId];
                blockRows.push(<Row key={rowId}
                                    name={row.name}
                                    leftAlign={row.leftAlign}
                                    orphanCheck={this.props.orphanCheck}
                                    onSeatSel={this.props.onSeatSel}
                                    seatRotate={seatRotate}
                                    avail={this.props.avail}
                                    mine={this.props.mine}
                                    seats={row.seats} />);
            }
        }

        return <div className="block" style={style}>
            <span className="blockName">{this.props.name}</span>
            {blockRows}
        </div>;
    }
});

var BasketTimeout = React.createClass({
    calcDiff: function() {
        var time = new Date(this.props.basket.created);
        time.setMinutes(time.getMinutes() + appState.config.expiryMins);
        this.setState({diff: new Date(time - Date.now())});
    },
    componentWillMount: function() {
        this.calcDiff();
    },
    componentDidMount: function() {
        this.setState({interval: window.setInterval(this.calcDiff, 1000)});
    },
    componentWillUnmount: function() {
        window.clearInterval(this.state.interval);
    },
    render: function() {
        var classes = "plate seatsHeld";
        var diff = this.state.diff;

        if (diff > 0)
            var diffText = '' + diff.getMinutes() + 'm ' + diff.getSeconds() + 's';
        else
            var diffText = '0m 0s';

        if (!diff.getMinutes())
            classes += ' urgent';

        return <div className={classes}>The selected seats have been held for you.<br />
                <strong>You have {diffText} to complete your booking before these seats will be released.</strong>
                </div>;
    }
});

var SeatPicker = React.createClass({
    render: function()
    {
        var blockData = this.props.seatmap.get('blocks');

        if (!blockData)
            return <p>Loading...</p>;

        var blocks = [];
        var takenData  = {}
        takenArray = this.props.availability.get('takenSeats');

        var mineData = {}
        var perf     = this.props.perf;
        this.props.basket.each(function(seat) {
            if (seat.get('performance') == perf.id)
                mineData[seat.attributes.id] = true;
        });

        for (var i = 0; i < takenArray.length; i++)
            takenData[takenArray[i]] = true;

        for (var blkId in blockData) {
            if (!blockData.hasOwnProperty(blkId))
                continue;

            blk = blockData[blkId];

            blocks.push(<Block
                            key={blkId}
                            avail={takenData}
                            mine={mineData}
                            name={blk.name}
                            rows={blk.rows}
                            orphanCheck={this.props.orphanCheck}
                            onSeatSel={this.props.onSeatSel}
                            rotate={blk.rotation}
                            left={blk.offset[0]}
                            top={blk.offset[1]} />);
        }

        var heldTimer = this.props.basket.created ? <BasketTimeout basket={this.props.basket} /> : null;

        return <div><h2>Select seats for {this.props.show.get('name')}</h2>
                    <div className="basketTimeout">
                        {heldTimer}
                    </div>
                    <h3>Viewing {this.props.perf.startsAt.toLocaleDateString()}
                        &nbsp;@&nbsp;{appCommon.formatTime(this.props.perf.startsAt)}
                        <small>
                            &nbsp;<a href={"#/shows/" + this.props.show.id}>(Choose another performance)</a>
                        </small>
                    </h3>
                    <p>{this.props.show.get('description')}</p>
                    <p>{this.props.perf.description}</p>
                    <div className="key">
                        Key:
                        <div className="seat selected">1</div> Seat in basket
                        <div className="seat taken">1</div> Seat unavailable
                        <div className="seat restricted">1</div> Restricted view
                    </div>
                    <div className="blockZone">
                    {blocks}
                    </div>
            </div>;
    }
});

module.exports = {Seat: Seat,
                  Row: Row,
                  Block: Block,
                  BasketTimeout: BasketTimeout,
                  SeatPicker: SeatPicker};
