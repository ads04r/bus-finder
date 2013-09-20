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

$(document).bind("pageinit", function()
{
	setUpTabs();
});

$(document).ready(function() 
{
		// Mobile version

		geoLocate();
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
						if(lasttitle != title) {
							html = html + '<li data-theme="c"><a href="/search/mobile.html?uri=' + encodeURIComponent(uri) + '&lat=' + lat + '&lon=' + lon + '" data-transition="slide">' + title + '</a></li>';
						}
						lasttitle = title;
					});
					$("#resultslist").html(html);
					$("#resultslist").listview('refresh');
				});
			}
		});
});
