module.exports = {
    parseDate: function(string) {
            var regex = /^(\d{4})-(\d{2})-(\d{2}) (\d{2}):(\d{2}):(\d{2})$/;
            var parts = regex.exec(string);
            return new Date(Date.UTC(parts[1], parts[2] -1, parts[3], parts[4], parts[5],parts[6]));
    },
    formatTime: function(time) {
        var pad = function(num) {
            var s = String(num);
            if (s.length == 1)
                s = "0" + s;

            return s;
        };
        return pad(time.getHours()) +
               ':' + pad(time.getMinutes());
    },
    classifyCompletionError: function(e) {
        var generic = "Something went wrong which we can't identify, sorry.";
        if (!('status' in e))
            return generic;

        switch (e.status) {
            case 400: return "Your booking request appeared to be faulty.";
            case 401: return "This booking could not be processed as security data were missing.";
            case 402: return "We failed to take payment from your card. The card may be expired, invalid or declined. Please try again, or try another card.";
            case 403: return "Permission to create a booking was denied.";
            case 404: return "You appear to have attempted to make a booking for zero seats.";
            case 409: return "Your seats have been invalidated as you took longer than 15 minutes to book. Please select new seats and try again.";
            case 410: return "You appear to have attempted to process the same booking twice.";
            case 500: return "Our booking system experienced an internal problem.";
            case 503: return "A conflict was detected when trying to secure your booking. Please close any other browser windows open on this site.";
            default: return generic;
        }
    }
};
