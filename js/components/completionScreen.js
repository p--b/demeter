var React = require('react');

module.exports = {
    done: React.createClass({
        render: function() {
        return <div>
        <h1>Thanks!</h1>
        <article className="complete">
            <p className="tick"></p>
            <h2>Your tickets have been successfully booked.</h2>
            <p>You will receive an e-mail containing your tickets.</p>
            <p>You should <strong>print your tickets</strong>, and bring them with you.</p>
            <p>Thank-you for using the MTSoc Online Ticketing system!</p>
        </article></div>;
        },
    }),
    working: React.createClass({
        render: function() {
            return <div>
                <h1>Please Wait</h1>
                <article className="working">
                <p className="spin"></p>
                <h2>Hold tight, processing your payment...</h2>
                <p>If you see this screen for a long time, please try refreshing.</p>
                <p>You won&apos;t be charged twice.</p>
                </article>
                </div>
        },
    }),
};
