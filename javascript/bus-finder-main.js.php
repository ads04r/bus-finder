<?php  header("Content-type: text/plain"); ?>
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
	$("#main").find(".content").html("<h2>Loading bus data...</h2>");
	bl = BusListener( stops, drawStops, function( msg ) { ; }	);
}

$(document).ready(function() {

	$('#searchfield').autocomplete({
		source: "autocomplete.json",
		minLength: 3,
		select: function(event, ui) {
			var uri = ui.item.id;
			$('#searchuri').attr('value', uri);
		}
	});

	$('#campusselect').find('a').click(function() {
		var uri = $(this).attr('href');

<?
$points = json_decode(file_get_contents("../config/startpoints.json"), true);
$mainpoint = array();
foreach($points as $point)
{
	if(count($point['stops']) < 1)
	{
		continue;
	}
	if(count($mainpoint) == 0)
	{
		$mainpoint = $point;
	}
?>

		if(uri == '<? print($point['uri']); ?>')
		{
			bl.destroy();
			var stops = ["<? print(implode("\",\"", $point['stops'])); ?>"];
			$('#sourceuri').attr('value', uri);
			create("<? print($point['title']); ?>", stops);
			return false;
		}

<? } ?>

	});

<? if(count($mainpoint) > 0) { ?>

	var stops = ["<? print(implode("\",\"", $mainpoint['stops'])); ?>"];
	create("<? print($mainpoint['title']); ?>", stops);

<? } ?>

});


