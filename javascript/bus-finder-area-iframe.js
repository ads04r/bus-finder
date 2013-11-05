var bl;

function drawStops( data )
{
	var h = "<table>";

	var count = 0;
	for (var i in data["stops"]) { if (data["stops"].hasOwnProperty(i)) { count++; }}

	for( var i=0; i<data["journeys"].length; i++ )
	{
		var jny = data["journeys"][i];
		var label = jny[0]["name"]+" to "+jny[0]["dest"];
		for( var j=0;j<jny.length;j++ )
		{
			var stop = data["stops"][jny[j]["stop"]];

			if( j==0 )
			{
				h += '<tr><th colspan="2">'+label+"</td></tr>";
			}

			h += '<tr><td><a href="/bus-stop/' + stop['code'] + '.html">' + stop['label'] + '</a></td><td><div class="time">' + jny[j]["time"] + '</td></tr>';

		}
	}
	h += "</table>";

	$("#main").find(".place_stops").html( h );
}

function create(title, stops)
{
	$("#listtitle").text(title);
	$("#main").find(".place_stops").html("<h2>Loading bus data...</h2>");
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

	var url = "/area/" + $(".place_stops").attr('id') + ".json";
	
	$.ajax({
		type: "GET",
		url: url,
		dataType: "json",
		success: function(data) {
			var stops = data['stops'];
			var title = data['title'];
			create(title, stops);
		},
		error: function() {
		}
	});

});


