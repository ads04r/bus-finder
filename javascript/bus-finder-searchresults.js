var bl;
var routes;
var stops_start;
var stops_end;

function drawStops( data )
{
	var h = "";

	var count = 0;
	for (var i in data["stops"]) { if (data["stops"].hasOwnProperty(i)) { count++; }}

	for( var i=0; i<data["journeys"].length; i++ )
	{
		var jny = data["journeys"][i];
		var label = jny[0]["name"]+" to "+jny[0]["dest"];

		var rtok = 0;
		for(var key in routes)
		{
			if(routes.hasOwnProperty(key))
			{
				var rt = routes[key];
				var rtcode = rt['id'] + ' ';
				var clen = rtcode.length;
				var compare = jny[0]["name"].substring(0, clen) + " ";
				if(compare == rtcode)
				{
					rtok = 1;
				}
			}
		}

		if((jny.length > 1) & (rtok == 1))
		{
			var firststop = jny[0]["stop"];
			var finalstop = jny[(jny.length - 1)]["stop"];

			if(($.inArray(firststop, stops_start) > -1) & ($.inArray(finalstop, stops_end) > -1))
			{
				for( var j=0;j<jny.length;j++ )
				{
					var stop = data["stops"][jny[j]["stop"]];
		
					if( j==0 )
					{
						h += "<h2>"+label+"</h2>";
						h += "<div>";
					}
		
					h += '<div class="entry"><a href="/bus-stop/' + stop['code'] + '.html"><h3>' + stop['label'] + '</h3></a><div class="time">' + jny[j]["time"] + '</div></div>';
		
					if( j==(jny.length - 1) )
					{
						h += "</div>";
					}
				}
			}
		}
	}

	$("#main").find(".route_stops").html( h );
}

function create(title, stops)
{
	$("#main").find(".route_stops").html("<h2>Loading bus data...</h2>");
	bl = BusListener( stops, drawStops, function( msg ) { ; } );
}

$(document).ready(function() {

	$('#searchfield').autocomplete({
		source: "/search/autocomplete.json",
		minLength: 3,
		select: function(event, ui) {
			var uri = ui.item.id;
			$('#searchuri').attr('value', uri);
		}
	});

	var url = document.URL.replace('.html?', '.json?');
	
	$.ajax({
		type: "GET",
		url: url,
		dataType: "json",
		success: function(data) {
			routes = data['routes'];
			var stops_arr = data['stops'];
			stops_start = stops_arr['start'];
			stops_end = stops_arr['end'];
			var stops = stops_arr['start'].concat(stops_arr['end']);
			var title = "Search";
			create(title, stops);
		},
		error: function() {
		}
	});

});


