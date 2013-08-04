<?php

$f3->set('page_title', '');

$stops = json_decode(file_get_contents("./config/stops.json"), true);
$routes = json_decode(file_get_contents("./config/routes.json"), true);

print("<p>Welcome to bus.southampton.ac.uk, a service from the University of Southampton's Open Data team. This site combines various open datasets to provide a useful service for anyone wanting to use Southampton's bus network.</p>");

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
foreach($routes as $route)
{
	print("<li><a href=\"" . $route['url'] . "\">" . $route['num'] . " to " . $route['dest'] . "</a></li>");
}
print("</ul></td>");
print("</tr>");
print("</table>");

print("<h2>Open Source</h2>");
print("<p>The data and code used to make this site is all open source. Bus data is provided by Southampton Council and made available by the University of Southampton.</p>");
print("<p><a href=\"https://github.com/ads04r/bus-finder\">Site source code available on Github.</a></p>");
print("<p><a href=\"http://data.southampton.ac.uk/dataset/bus-info.html\">Bus data available from the University's Open Data Service.</a></p>");
