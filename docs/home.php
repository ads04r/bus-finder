<?php

$f3->set('page_title', '');

function sort_unilink_routes($a, $b)
{
	$ret = strcmp($a['notation'], $b['notation']);
	if($ret != 0) { return($ret); }
	if($a['stop_count'] > $b['stop_count'])
	{
		return 1;
	}
	return -1;
}

$stops = json_decode(file_get_contents("./config/stops.json"), true);
$routes = json_decode(file_get_contents("./config/routes.json"), true);
$unilink_routes = json_decode(file_get_contents("./cache/unilink.json"), true);
$unilink_dests = array(
	"U1A" => "Southampton Airport",
	"U1C" => "Southampton City Centre",
	"U1N" => "Eastleigh (Night Bus)",
	"U1W" => "Wessex Lane",
	"U2" => "Highfield Interchange",
	"U2B" => "Bassett Green",
	"U2C" => "Southampton City Centre",
	"U2X" => "Highfield Interchange",
	"U6" => "Highfield Interchange",
	"U6C" => "Southampton City Centre",
	"U6H" => "Southampton General Hospital"
);

usort($unilink_routes, 'sort_routes');

print("<p>Welcome to bus.southampton.ac.uk, a service from the University of Southampton's Open Data team. This site combines various open datasets to provide a useful service for anyone wanting to use Southampton's bus network.</p>");
print("<p>This site relies on live data provided by the council. Occasionally this goes down for maintenance, please bear with us. The bus finder feature should be unaffected by these outages.</p>");
print("<p>Using a mobile device? Try <a href=\"http://bus.southampton.ac.uk/mobile\">bus.southampton.ac.uk/mobile</a> for a more optimised version!</p>");

print("<h2>University Bus Information</h2>");
print("<table class=\"headerlayout\">");
print("<tr>");
print("<td><h3>Bus Stops</h3></td>");
print("<td><h3>Unilink Routes</h3></td>");
print("</tr>");
print("<tr>");
print("<td><ul>");
foreach($stops as $stop)
{
	print("<li><a href=\"/bus-stop/" . $stop['id'] . ".html\">" . $stop['name'] . "</a></li>");
}
print("</ul></td>");
print("<td><ul>");
$last_service = "";
foreach($unilink_routes as $route)
{
	if(strcmp($last_service, $route['notation']) == 0) { continue; }
	$rr = explode(" - ", $route['label']);
	$dest = $rr[(count($rr) - 1)];
	if(array_key_exists($route['notation'], $unilink_dests))
	{
		$dest = $unilink_dests[$route['notation']];
	}
	print("<li><a href=\"" . $route['route'] . "\">" . $route['notation'] . " to " . $dest . "</a></li>");
	$last_service = $route['notation'];
}
print("</ul></td>");
print("</tr>");
print("</table>");

print("<h2>Open Source</h2>");
print("<p>The data and code used to make this site is all open source. Bus data is provided by Southampton Council and made available by the University of Southampton.</p>");
print("<p><a href=\"https://github.com/ads04r/bus-finder\">Site source code available on Github.</a></p>");
print("<p><a href=\"http://data.southampton.ac.uk/dataset/bus-info.html\">Bus data available from the University's Open Data Service.</a></p>");
