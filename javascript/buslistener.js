function BusListener( stops, callback, logFn )
{
	if( logFn == undefined ) { logFn = function( x) {;}; }
	this.stops = stops;
	this.callback = callback;
	this.data = {"stops":{}, "events":{}, "timeouts":{}, "journeys":{}, "ids":stops };
	bl = this;

	function journeyTimeCompare(a,b) { 
		// not dealing with midnight yet
		if( a[0]["time"]<b[0]["time"] ) { return -1; }
		if( a[0]["time"]>b[0]["time"] ) { return 1; }
		return 0;
	};
	function eventTimeCompare(a,b) { 
		// not dealing with midnight yet
		if( a["time"]<b["time"] ) { return -1; }
		if( a["time"]>b["time"] ) { return 1; }
		return 0;
	};

	this.munging = false;
	this.remunge = false;
	this.getStop = function( code ) { 
		var url = "/bus-stop/"+code+".json?max=30";
		logFn( "ajax url: "+url);
		bl.data["timeouts"][code]=((new Date()).getTime()/1000) + 60; // default
		$.getJSON( url, function(info) {
			logFn( "id: "+info["code"]+" age "+info["age"] );
			bl.data["stops"][info["code"]] = info;
			if (info["age"] < 30) // If the age is over 30 then clearly there's some network issues. We don't want to overload the server(s).
			{
				bl.data["timeouts"][info["code"]]=(((new Date()).getTime()/1000) - info["age"]) + 30;
			}
			munge();

		}) .fail(function( jqxhr, textStatus, error ) {
			logFn( "ERROR: "+jqxhr.status+" : "+url);
			logFn( "ERROR: "+jqxhr.responseText+" : "+url);
			bl.data["timeouts"][code]=((new Date()).getTime()/1000) + 15;
		});
	};

	function munge()
	{
		this.remunge=true;
		if( this.munging )
		{
			return;
		}
		this.munging = true;
		while( this.remunge )
		{
			this.remunge = false; // can become true again via other call to munge
			var events = [];
			var journeys = {};
			for( var code in bl.data["stops"] )
			{
				for( var k=0; k<bl.data["stops"][code]["stops"].length; ++k )	
				{
					var event = bl.data["stops"][code]["stops"][k];
					event["stop"] = code;
					event["jid"] = event["name"]+":"+event["dest"]+":"+event["journey"];
					events.push( event );
					if( journeys[ event["jid"] ] == undefined )
					{
						journeys[ event["jid"] ] = [];
					}
					journeys[ event["jid"] ].push( event );
				}
			}
			var j2 = [];
			for( var jid in journeys )
			{
				var list = journeys[jid];
				var sortedList = journeys[jid].sort( eventTimeCompare );
				j2.push( sortedList );
			}
			bl.data["events"] = events.sort( eventTimeCompare );
			bl.data["journeys"] = j2.sort( journeyTimeCompare );
		}
		this.munging = false;
		(callback)( bl.data );
	}

	function updateStops()
	{
		logFn( "TICK" );
		var now = ((new Date()).getTime()/1000);
		for( i=0; i<bl.stops.length; ++i )
		{
			var code = this.stops[i];
			logFn( "/ "+code+" / "+bl.data["timeouts"][code]+" / "+now);
			if( bl.data["timeouts"][code] < now )
			{
				bl.getStop( code );
			}
		}
	}
	
	logFn( "BEGIN" );
	// load all data once
	for( i=0; i<this.stops.length; ++i )
	{
		this.getStop( this.stops[i] );
	}
	setInterval( updateStops, 3000 );
	
	logFn( "END" );

	return this;
}
