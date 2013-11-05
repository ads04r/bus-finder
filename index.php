<?php

include_once("./include/datasearch.php");
include_once("./include/functions.php");

$f3 = require("fatfree/lib/base.php");
$f3->set('DEBUG', true);
$f3->set('page_template', "");

// Website settings

$f3->set('brand_file', "./templates/bus.html");
$f3->set('mobile_brand_file', "./templates/mobile.html");
//$f3->set('sparql_endpoint', "http://sparql.data.southampton.ac.uk/");
$f3->set('sparql_endpoint', "http://edward:8002/sparql/");
$f3->set('map_url', "http://kanga-cb15g11.ecs.soton.ac.uk/sum/[Z]/[X]/[Y].png");

// Classes

include_once("./classes/BusStop.php");
include_once("./classes/BusRoute.php");
include_once("./classes/Place.php");
include_once("./classes/Area.php");
include_once("./classes/Search.php");

// Render functions

include_once("./include/render.php");
include_once("./include/search.php");
include_once("./include/maptiles.php");

// Routes

$f3->route("GET /", "homePage");
$f3->route("GET /area/@areaid.@format", "busArea");
$f3->route("GET /bus-route/@routecode.@format", "busRoute");
$f3->route("GET /bus-stop/@stopcode.@format", "busStop");
$f3->route("GET /bus-stop/@stopcode.@format?max=@maxrows", "busStop");
$f3->route("GET /place/@fhrs.@format", "place");

$f3->route("GET /mobile", "mobileHomePage");
$f3->route("GET /bus-route-mobile/@routecode.@format", "mobileBusRoute");
$f3->route("GET /bus-stop-mobile/@stopcode.@format", "mobileBusStop");

$f3->route("GET /area-publicdisplay/@areaid.@format", "publicdisplayBusArea");
$f3->route("GET /area-iframe/@areaid.@format", "iframeBusArea");
$f3->route("GET /bus-stop-publicdisplay/@stopcode.@format", "publicdisplayBusStop");
$f3->route("GET /bus-stop-iframe/@stopcode.@format", "iframeBusStop");

$f3->route("GET /search/autocomplete.json?term=@query", "autocompleteJson");
$f3->route("GET /search/finder.html", "searchPage");
$f3->route("GET /search/finder.@format?@argv", "searchPage");
$f3->route("GET /search/mobile.html?@argv", "mobileSearchPage");
$f3->route("GET /search/mobile.json?@terms", "mobileAutocompleteJson");
$f3->route("GET /search/mobile-route.@format?@terms", "mobileRoutePage");

$f3->route("GET /graphics/map/tiles/@z/@x/@y.png", "renderTile");

$f3->route("GET /@pagename.html", "otherPage");
$f3->route("GET *", function($f3) { $f3->error(404); });

$f3->run();
