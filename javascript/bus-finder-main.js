var bl;

function drawStops( data )
{
	var h = "";

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
				h += "<h2>"+label+"</h2>";
				h += "<div>";
			}
			
			h += '<div class="entry"><a href="' + stop['uri'] + '"><h3>' + stop['label'] + '</h3></a><div class="time">' + jny[j]["time"] + '</div></div>';
			
			if( j==(jny.length - 1) )
			{
				h += "</div>";
			}
		}
	}

	$("#main").find(".content").html( h );
}

function create(title, stops)
{
	$("#listtitle").text(title);
	bl = BusListener( stops, drawStops, function( msg ) { ; }	);
}

$(document).ready(function() {

	var stops = ["1980SN120134","1980HAA13668","1980SN120131","1980SN120127","1980SN120257","1980SN120256","1980SN120136","1980SN120128"];
	create("Highfield Campus", stops);
	
});
