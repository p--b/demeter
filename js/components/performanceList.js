var React = require('react');

module.exports = React.createClass({
    render: function() {

        var key = function(date) {
            return [date.getDate(),
                    date.getMonth(),
                    date.getYear()].join('');
        }

        var days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday',
                    'Thursday', 'Friday', 'Saturday'];
        var dayBins    = [];
        var dayBinData = [];
        var currentBin = -1;
        var lastKey    = null;
        var rows       = 0;
        var maxRows    = 0;

        var performances = this.props.show.get('performances');

        performances.forEach(function(perf) {
            var thisKey = key(perf.startsAt);

            if (thisKey != lastKey)
            {
                currentBin++;
                dayBins[currentBin] = [];
                dayBinData[currentBin] = perf.startsAt;

                if (++rows > maxRows)
                    maxRows = rows;

                rows = 0;
            }
            else
            {
                rows++;
            }

            dayBins[currentBin].push(perf);
            lastKey = thisKey;
        });

        maxRows = Math.max(rows, maxRows);

        var showId = this.props.show.id;
        var pad = function(num) {
            var s = String(num);
            if (s.length == 1)
                s = "0" + s;

            return s;
        };

        var link = function(perf) {
            return <a href={'#/shows/' + showId + '/' + perf.id}>
                        {pad(perf.startsAt.getHours())}{pad(perf.startsAt.getMinutes())}
                    </a>
        }

        var rows = [];

        for (var rowCount = 0; rowCount < maxRows; rowCount++)
        {
            var row = [];

            for (var day = 0; day <= currentBin; day++)
            {
                if (rowCount in dayBins[day])
                    row.push(<td key={day}>{link(dayBins[day][rowCount])}</td>);
                else
                    row.push(<td key={day}></td>);
            }

            rows.push(<tr key={rowCount}>{row}</tr>);
        }

        var headerRow = dayBinData.map(function(date) {
            return <th key={key(date)}>{days[date.getDay()]}<br />{date.toLocaleDateString()}</th>
        });

        return <div className="performanceList">
            <div className="plate">
                <h2>{this.props.show.get('name')}</h2>
                <h3>{this.props.show.get('fullName')}</h3>
                <h4>{this.props.show.get('venue')}</h4>
                <p>{this.props.show.get('description')}</p>
            </div>
            <p>Please select a performance:</p>
            <table>
                <thead>
                    <tr>{headerRow}</tr>
                </thead>
                <tbody>
                    {rows}
                </tbody>
            </table>
            </div>;
    }
});
