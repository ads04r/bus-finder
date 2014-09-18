var bl;
var routes;
var stops_start;
var stops_end;
var routeid;

function updateTimes() {
	var stopId = $('.bus_timetable').attr('id');
	var url = "/bus-stop/" + stopId + ".json";
	$.ajax({
		async: false,
		url: url,
		dataType: "json",
		success: function(json) {
			var dataAge = json['age'];
			var stops = json['stops'];
			var stopCount = stops.length;
			var htmlcode = "";
			for(var i = 0; i < stopCount; i++) {
				s = stops[i];
				var veh = '';
				if(s.vehicle) {
					veh = s.vehicle;
				}
				htmlcode = htmlcode + "<tr>"
				htmlcode = htmlcode + '<td class="time">' + s.time + '</td>'
				htmlcode = htmlcode + '<td class="routeid">' + s.name + '</td><td>' + s.dest + '</td>'
				if(veh.length > 0){
					htmlcode = htmlcode + '<td class="timetype"></td>'
				} else {
					htmlcode = htmlcode + '<td class="timetype">(Scheduled)</td>'
				}
				htmlcode = htmlcode + "</tr>"
			}
			if(htmlcode == '') {
				htmlcode = "<p>Error fetching data.</p>";
			} else {
				htmlcode = '<table class="bustimetable">' + htmlcode + "</table>";
			}
			$('.bus_timetable').html(htmlcode);
			if(dataAge < 30) {
				setTimeout(updateTimes, ((30 - dataAge) * 1000));
			} else {
				setTimeout(updateTimes, 30000);
			}
		},
		error: function() {
		}
	});
}

function drawStops( data )
{
	var h = "";

	var count = 0;
	for (var i in data["stops"]) { if (data["stops"].hasOwnProperty(i)) { count++; }}

	for( var i=0; i<data["journeys"].length; i++ )
	{
		var jny = data["journeys"][i];
		var label = jny[0]["name"]+" to "+jny[0]["dest"];

		//console.log("Checking... " + routeid + " vs " + label);
		if(label.substring(0, (routeid.length + 1)) != routeid + ' ') { continue; }

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
						h += "<table>";
					}
		
					h += '<tr>'
					h += '<td>' + jny[j]["time"] + '</td>';
					h += '<td><a href="/bus-stop-mobile/' + stop['code'] + '.html"><strong>' + stop['label'] + '</strong></a></td>'
					h += '</tr>'
		
					if( j==(jny.length - 1) )
					{
						h += "</table>";
					}

				}
			}
		}
	}

	$("#mobile_live_times").html( h );
}

function create(title, stops)
{
	routeid = $("div#route_id").text();

	$("#mobile_live_times").html("<h2>Loading bus data...</h2>");
	bl = BusListener( stops, drawStops, function( msg ) { ; } );
}

function geoLocate() 
{
	navigator.geolocation.getCurrentPosition(
		function(position) {
			$('#latitude').attr('value', position.coords.latitude);
			$('#longitude').attr('value', position.coords.longitude);
		}
	);
}

function showPage(new_active)
{
	$('.routepageselect').each(function() {
		var id = "page_" + $(this).attr('data-tab-class');
		if(id == new_active)
		{
			$('#' + id).css('display', 'block');
		}
		else
		{
			$('#' + id).css('display', 'none');
		}
	});
}

function setUpTabs() 
{
	$(".routepageselect").click(function() {
		var new_active = 'page_' + $(this).attr('data-tab-class');
		showPage(new_active);
	});
}

$(document).bind("pageshow", function()
{
		if($('div.bus_timetable').length > 0)
		{
			updateTimes();
		}

		setUpTabs();

		if(($('#latitude').length > 0) & ($('#longitude').length > 0))
		{
			geoLocate();
		}

		$("#bussearch").on("change", function(event, ui) {
			var squery = $("#bussearch").attr('value');
			if (squery.length > 1) {
				var lat = $("#latitude").attr('value');
				var lon = $("#longitude").attr('value');
				var html = '';
				if ((lat.length > 0) & (lon.length > 0)) {
					var url = './search/mobile.json?lat=' + lat + '&lon=' + lon + '&q=' + encodeURIComponent(squery);
				} else {
					var url = './search/mobile.json?q=' + encodeURIComponent(squery);
				}
				$.getJSON(url, function(data) {
					var lasttitle = '';
					$("#resultslist").html('');
					$.each(data, function(key, val) {
						var title = val['title'];
						var uri = val['uri'];
						var ds = val['dataset'];
						if(lasttitle != title) {
							html = html + '<li data-theme="c"><a href="/search/mobile.html?uri=' + encodeURIComponent(uri) + '&lat=' + lat + '&lon=' + lon + '" data-transition="slide">' + title + '<br><span class="poi_source">Source: ' + ds['title'] + '</span></a></li>';
						}
						lasttitle = title;
					});
					$("#resultslist").html(html);
					$("#resultslist").listview('refresh');
				});
			}
		});

		if($('#mobile_live_times').length > 0)
		{
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
		}
});
