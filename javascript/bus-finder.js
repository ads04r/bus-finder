function geoLocate() {
	navigator.geolocation.getCurrentPosition(
		function(position) {
			$('#latitude').attr('value', position.coords.latitude);
			$('#longitude').attr('value', position.coords.longitude);
		}
	);
}

$(document).ready(function() {

	if(($('#searchfield').length != 0) | ($('#routes-list').length != 0)) {

		// Desktop version

		$('#searchfield').autocomplete({
			source: "autocomplete.json",
			minLength: 3,
			select: function(event, ui) {
				var uri = ui.item.id;
				$('#searchuri').attr('value', uri);
			}
		});
		$('#routes-list').accordion({
			autoHeight: false,
			collapsible: true
		});

	} else {

		// Mobile version

		geoLocate();
		$("#bussearch").on("change", function(event, ui) {
			var squery = $("#bussearch").attr('value');
			if (squery.length > 1) {
				var lat = $("#latitude").attr('value');
				var lon = $("#longitude").attr('value');
				var html = '';
				if ((lat.length > 0) & (lon.length > 0)) {
					var url = './search.json?lat=' + lat + '&lon=' + lon + '&q=' + encodeURIComponent(squery);
				} else {
					var url = './search.json?q=' + encodeURIComponent(squery);
				}
				$.getJSON(url, function(data) {
					var lasttitle = '';
					$("#resultslist").html('');
					$.each(data, function(key, val) {
						var title = val['title'];
						var uri = val['uri'];
						if(lasttitle != title) {
							html = html + '<li data-theme="c"><a href="route.html?view=mob&uri=' + encodeURIComponent(uri) + '&lat=' + lat + '&lon=' + lon + '" data-transition="slide">' + title + '</a></li>';
						}
						lasttitle = title;
					});
					$("#resultslist").html(html);
					$("#resultslist").listview('refresh');
				});
			}
		});

	}

});
