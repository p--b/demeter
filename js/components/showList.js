var React = require('react');

module.exports = React.createClass({
    render: function() {
        var shows = this.props.shows.map(function(show)
        {
            var style = {
                backgroundImage: "url(res/shows/"+show.id+"/listBanner.png)",
            };

            return <a key={show.id} href={"#/shows/" + show.id}>
                    <article className="show" style={style}>
                        <h1>{show.get('name')}</h1>
                        <h2>{show.get('fullName')}</h2>
                        <h3>{show.get('venue')}</h3>
                    </article>
                   </a>
        });

        return <div>
            <h1>Welcome to the MTSoc Online Box Office</h1>
            <h4>Buy tickets for shows quickly, easily, and securely.</h4>
            <h3>Select a show to book tickets:</h3>
            <div className="showList">
                {shows}
            </div>
        </div>
    }
});
