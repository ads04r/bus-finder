function busTimer(stopcode)
{
	var url = "http://bus.southampton.ac.uk/bus-stop/" + stopcode + ".json";
	$.ajax(
	{
		type: "GET",
		url: url,
		dataType: "xml",
		success: function(xml)
		{
			
		}
	});
}
