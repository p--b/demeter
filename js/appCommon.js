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
};
