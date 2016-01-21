var React = require('react')

var Seat = React.createClass({
    render: function() {
        var props = this.props
        var seatSelect = function() {
            console.log(props)
        }
        var classes = 'seat'

        if (this.props.selected) {
            classes += ' selected'
        }

        if (this.props.restricted) {
            classes += ' restricted'
        }

        if (this.props.taken) {
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

        var rowSeats = this.props.seats.map(function(seat) {
            return <Seat key={seat.id}
                         style={style}
                         id={seat.id}
                         number={seat.number}
                         restricted={seat.restricted}
                         taken={seat.taken}
                         selected={seat.selected} />
        });

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
            style.left = this.props.left
            style.position = 'absolute'
        }

        if (this.props.top) {
            style.top = this.props.top
            style.position = 'absolute'
        }

        if (this.props.rotate) {
            style.transform = 'rotate(' + this.props.rotate + 'deg)'

            seatRotate = '-' + this.props.rotate
        }

        for (var rowName in this.props.rows) {
            if (this.props.rows.hasOwnProperty(rowName)) {
                row = this.props.rows[rowName]
                    blockRows.push(<Row key={rowName}
                                        name={rowName}
                                        seatRotate={seatRotate}
                                        seats={row} />)
            }
        }

        return <div className="block" style={style}>
            <span className="blockName">{this.props.name}</span>
            {blockRows}
        </div>
    }
});

module.exports = {Seat: Seat, Row: Row, Block: Block}
