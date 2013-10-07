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

$(document).ready(function() {

	$('#searchfield').autocomplete({
		source: "/search/autocomplete.json",
		minLength: 3,
		select: function(event, ui) {
			var uri = ui.item.id;
			$('#searchuri').attr('value', uri);
		}
	});

	updateTimes();

});



