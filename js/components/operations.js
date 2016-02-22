var React = require('react')
var appCommon = require('../appCommon.js');

module.exports = {
    BoxOffice: React.createClass({
        getInitialState: function() {
            return {
                token: null,
                key: null,
                tag: "",
                sequence: 0,
            };
        },
        render: function() {
            var update = (function(stateKey, e) {
                var stateObj = {sequence: 0};
                stateObj[stateKey] = e.target.value;
                this.setState(stateObj);
            }).bind(this);

            var getResultState = (function() {
                var res = this.props.lastResult;

                if (res == null)
                    return "Ready";

                if ('error' in res)
                    return "Error: " + res.error.status + ": " + appCommon.classifyCompletionError(res.error);

                if ('success' in res) {
                    var tixLink = <button
                                     onClick={this.props.getTickets.bind(this,
                                                                         res.success,
                                                                         this.state.key)}>
                                        Get Tickets
                                   </button>;
                    return <span>Success! ID: {res.success} {tixLink}</span>
                }

                return "[Unknown]";
            }).bind(this);

            return <div>
                <h1>Cash Desk</h1>
                <table>
                    <tbody>
                        <tr><td>API Token</td>
                            <td><input type="password" onChange={update.bind(this, 'token')} /></td></tr>
                        <tr><td>API Key</td>
                            <td><input type="password" onChange={update.bind(this, 'key')} /></td></tr>
                        <tr><td>Cash Desk Tag</td>
                            <td><input type="text" onChange={update.bind(this, 'tag')} /></td></tr>
                        <tr><td>Next Sequence #:</td>
                            <td>{this.state.sequence}</td></tr>
                    </tbody>
                </table>
                <button
                    onClick={this.props.cashTokenProcessor.bind(this,
                                                                this.state.token,
                                                                this.state.tag,
                                                                this.state.sequence)}>
                    VEND
                </button><br />
                <p className="plate">Result: <strong>{getResultState()}</strong></p>
            </div>;
        },
    }),
    EntryChecker: React.createClass({
        getInitialState: function() {
            return {
                key: null,
                hmacSecret: null,
            };
        },
        render: function() {
            var update = (function(stateKey, e) {
                var stateObj = {sequence: 0};
                stateObj[stateKey] = e.target.value;
                this.setState(stateObj);
            }).bind(this);

            var getResultState = (function() {
                var res = this.props.result;

                if (res == null)
                    return "Ready.";

                if ('success' in res)
                    return 'Ticket Valid!';

                if ('invalid' in res)
                    return res.invalid;

                if ('pending' in res)
                    return res.pending;

                if ('error' in res) {
                    switch (res.error.status) {
                        case 400: return "Invalid stub request!";
                        case 401: return "Valid authentication was not performed.";
                        case 402: return "Booking no longer holds this ticket!";
                        case 403: return "Not authorised to take stubs!";
                        case 404: return "Ticket not found in booking";
                        case 409: return "Ticket already processed!";
                        case 410: return "Ticket void: possibly refunded";
                    }
                }

                return "[Unknown]";
            }).bind(this);

            var getResultClass = (function() {
                var res = this.props.result;

                if (res == null)
                    return '';

                if ('error' in res || 'invalid' in res)
                    return 'bad';

                if ('success' in res)
                    return 'good';

                return '';
            }).bind(this);

            return <div>
                <h1>Ticket Checking</h1>
                <h2>{this.props.show.get('fullName')}</h2>
                <h3>{this.props.performance.startsAt.toLocaleDateString()} @&nbsp;
                {appCommon.formatTime(this.props.performance.startsAt)}</h3>
                <table>
                    <tbody>
                        <tr><td>API Key</td>
                            <td><input type="password" onChange={update.bind(this, 'key')} /></td></tr>
                        <tr><td>Ticket Validation Key</td>
                            <td><input type="password" onChange={update.bind(this, 'hmacSecret')} /></td></tr>
                    </tbody>
                </table>
                <p className={'plate big ' + getResultClass()}>
                    Barcode: <input
                                onChange={update.bind(this, 'barcode')}
                                onKeyUp={this.props.acceptStub.bind(this,
                                                                    this.state.barcode,
                                                                    this.state.key,
                                                                    this.state.hmacSecret)} />
                    <br />Result: <strong>{getResultState()}</strong>
                </p>
            </div>
        }
    })
};
