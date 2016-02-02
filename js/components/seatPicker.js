var React = require('react')

var Seat = React.createClass({
    render: function() {
        var props = this.props
        var seatSelect = function(e) {
            var list = e.target.classList;
            if (!list.contains('loading')) {
                if (!list.contains('taken') || list.contains('selected'))
                    list.add('loading');

                props.onSeatSel(props);
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

        return <div className={classes}
                    style={this.props.style}
                    onClick={seatSelect}>
            {this.props.number}
        </div>
    }
});

var Row = React.createClass({
    render: function() {
        var style    = {}

        if (this.props.seatRotate) {
            style.transform = 'rotate(' + this.props.seatRotate + 'deg)';
        }

        var rowSeats = [];

        for (var seatId in this.props.seats)
        {
            if (!this.props.seats.hasOwnProperty(seatId))
                continue;

            seat = this.props.seats[seatId];
            rowSeats.push(<Seat key={seatId}
                         onSeatSel={this.props.onSeatSel}
                         style={style}
                         id={seatId}
                         number={seat.seatNum}
                         restricted={seat.restricted}
                         taken={seatId in this.props.avail}
                         selected={seatId in this.props.mine} />);
        }

        return <div className="row">
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
            style.left = this.props.left + 'vw';
            style.position = 'absolute'
        }

        if (this.props.top) {
            style.top = this.props.top + 'vh';
            style.position = 'absolute'
        }

        if (this.props.rotate) {
            style.transform = 'rotate(' + this.props.rotate + 'deg)'

            seatRotate = '-' + this.props.rotate
        }

        for (var rowId in this.props.rows) {
            if (this.props.rows.hasOwnProperty(rowId)) {
                row = this.props.rows[rowId]
                    blockRows.push(<Row key={rowId}
                                        name={row.name}
                                        onSeatSel={this.props.onSeatSel}
                                        seatRotate={seatRotate}
                                        avail={this.props.avail}
                                        mine={this.props.mine}
                                        seats={row.seats} />)
            }
        }

        return <div className="block" style={style}>
            <span className="blockName">{this.props.name}</span>
            {blockRows}
        </div>
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
                mineData[seat.id] = true;
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
                            onSeatSel={this.props.onSeatSel}
                            rotate={blk.rotation}
                            left={blk.offset[0]}
                            top={blk.offset[1]} />);
        }

        return <div><h2>Select seats for {this.props.show.get('name')}</h2>
                    <h3>Viewing {this.props.perf.startsAt.toLocaleDateString()} @
                        {this.props.perf.startsAt.getHours()}
                        {this.props.perf.startsAt.getMinutes()}
                    </h3>
                    <div className="blockZone">
                    {blocks}
                    </div>
            </div>;
    }
});

module.exports = {Seat: Seat,
                  Row: Row,
                  Block: Block,
                  SeatPicker: SeatPicker};
