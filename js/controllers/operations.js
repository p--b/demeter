var React      = require('react');
var ReactDOM   = require('react-dom');
var Backbone   = require('backbone');
var CryptoJS   = require('crypto-js');
var BigInteger = require('jsbn').BigInteger;

var appState   = require('../appState.js');
var Operations = require('../components/operations.js');
var Booking    = require('../models/booking.js');
var ShowModel  = require('../models/show.js');

module.exports = {
    doEntry: function(showId, performanceId) {
        var result = null;
        var show   = null;
        var perf   = null;

        var render = function() {
            ReactDOM.render(
                <Operations.EntryChecker
                    result={result}
                    show={show}
                    acceptStub={acceptStub}
                    performance={perf} />,
            appState.Viewport);
        };

        var updateResult = function(newResult) {
            result = newResult;
            render();
        };

        var rebase = function(decimal, targetSet) {
            var base   = new BigInteger(targetSet.length.toString(), 10);
            var result = '';

            while (decimal.compareTo(BigInteger.ZERO)) {
                var index = decimal.mod(base);
                result  += targetSet[parseInt(index.toString())];
                decimal  = decimal.subtract(index);
                decimal  = decimal.divide(base);
            }

            return result;
        }

        var verifyHmac = function(data, hash, hmacSecret) {
            var brokenHexDec = function(hex) {
                var result  = new BigInteger('0', 10);
                var hexLen  = hex.length;
                var charset = '0123456789abcdef';
                var base    = new BigInteger('16', 10);

                for (var i = 1; i < hexLen; i++) {
                    var index = new BigInteger(charset.indexOf(hex[i - 1]).toString(), 10);
                    var place = new BigInteger((hexLen - i).toString(), 10);
                    result = result.add(base.pow(place).multiply(index));
                }

                return result;
            };

            var opts      = appState.config.hmac;
            var algorithm = opts.algo;
            var hexHash   = CryptoJS['Hmac' + algorithm](data, hmacSecret).toString();
            // FIXME: This is a bodge to get around incorrect hash generation.
            // See bchexdec() in DispatchCommand...
            var decHash   = brokenHexDec(hexHash);
            var validHash = rebase(decHash, opts.baseSet);

            return hash === validHash.substring(0, opts.truncate);
        };

        var submitStub = function(apiKey, bookingId, performanceId, seatId) {
            var data = { booking: bookingId };
            var opts = {
                performance: performanceId,
                seat: seatId,
            };
            var stub = new Booking.Stub(data, opts);

            stub.save({}, {
                headers: {
                    Authorization: "Demeter " + apiKey,
                },
                success: updateResult.bind(this, {success: true}),
                error: function(model, response) {
                    console.log("API error saving booking.stub");
                    console.log({model: model, response: response});
                    updateResult({error: response});
                },
            });
        };

        var acceptStub = function(barcode, key, hmacSecret, e) {
            if (e.keyCode != 13) // Only respond if enter was pressed
                return;

            e.target.value = "";

            if (!barcode || !key || !hmacSecret)
                return;

            var barcodeData   = barcode.split('%', 2);
            var barcodeFields = barcodeData[0].split('/');

            if (barcodeData.length < 2 || barcodeFields.length < 3)
                return updateResult({invalid: "Invalid barcode! Please scan again."});

            if (!verifyHmac(barcodeData[0], barcodeData[1], hmacSecret))
                return updateResult({invalid: "The ticket barcode is not genuine!"});

            if (barcodeFields[1] != perf.id)
                return updateResult({invalid: "The ticket is not for this show!"});

            updateResult({pending: "Ticket barcode valid. Checking if ticket void..."});
            submitStub(key, barcodeFields[0], barcodeFields[1], barcodeFields[2]);
        };

        var showModel = new ShowModel.Show({id: showId});
        showModel.on('sync change', function(sData) {
            show = sData;

            for (var i = 0; i < show.attributes.performances.length; i++)
            {
                if (show.attributes.performances[i].id == performanceId)
                {
                    perf = show.attributes.performances[i];
                    break;
                }
            }

            render();
        });
        showModel.fetch();
    },
    doBox: function() {
        var result = null;

        var onCash = function(token, tag, sequence) {
            this.setState({sequence: sequence + 1});
            var label = '* CASH *';
            var bk    = new Booking.Complete({
                name: label,
                email: appState.config.cashTicketSink,
                token: token + ':' + tag + ':' + sequence,
                source: 'cash',
                pymtAddrLine1: label,
                pymtAddrZip: label,
                pymtAddrState: label,
                pymtAddrCity: label,
                pymtAddrCountry: label,
            });

            bk.save({}, {
                success: function(model) {
                    result = {success: model.get('booking')};
                    render();
                },
                error: function(model, response) {
                    console.log("API error saving booking.complete");
                    console.log(response);
                    console.log(model);
                    result = {error: response};
                    render();
                }
            });
        };

        var getTickets = function(bookingId, key, e) {
            e.target.disabled = true;
            var target        = appState.config.endpoint + 'tickets/' + bookingId;
            var myXhr         = new XMLHttpRequest;

            Backbone.ajax(target, {
                headers: {
                    Authorization: "Demeter " + key,
                },
                dataType: "text",
                xhr: function() {
                    // Because who could /possibly/ need access to the raw request attribute?
                    // jQuery always knows best... oh, wait.
                    return myXhr;
                },
                xhrFields: {
                    responseType: 'blob',
                },
                success: function() {
                    var urlSrc = window.URL || window.webkitURL;
                    var url = urlSrc.createObjectURL(myXhr.response);
                    window.open(url);
                    window.setTimeout(urlSrc.revokeObjectURL.bind(this, url), 1000);
                },
                complete: function() {
                    e.target.disabled = false;
                }
            });
        };

        var render = function() {
            ReactDOM.render(
                <Operations.BoxOffice
                    cashTokenProcessor={onCash}
                    getTickets={getTickets}
                    lastResult={result} />,
            appState.Viewport);
        };
        render();
    }
}
