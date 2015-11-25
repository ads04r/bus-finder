<?php

function ajaxSearchPage($f3, $params)
{
	$format = "";
	if(array_key_exists("format", $params))
	{
		$format = $params['format'];
	}
	if(strlen($format) == 0)
	{
		$format = "html";
	}

	$sourceuri = "";
	$searchuri = "";
	$searchfield = "";
	if(array_key_exists("sourceuri", $_GET))
	{
		$sourceuri = $_GET['sourceuri'];
	}
	if(array_key_exists("searchuri", $_GET))
	{
		$searchuri = $_GET['searchuri'];
	}
	if(array_key_exists("searchfield", $_GET))
	{
		$searchfield = $_GET['searchfield'];
	}

	if(count($_GET) == 0)
	{
		renderPage($f3, "search");
		exit();
	}

	if(strcmp($format, "json") == 0)
	{
		header("Content-type: application/json");
		renderClarification($f3, $sourceuri, $searchuri, $searchfield, $format);
	}
}

function searchPage($f3, $params)
{
	$format = "";
	if(array_key_exists("format", $params))
	{
		$format = $params['format'];
	}
	if(strlen($format) == 0)
	{
		$format = "html";
	}

	if(strcmp($format, "json") == 0) { header("Content-type: application/json"); }

	$sourceuri = "";
	$searchuri = "";
	$searchfield = "";
	if(array_key_exists("sourceuri", $_GET))
	{
		$sourceuri = $_GET['sourceuri'];
	}
	if(array_key_exists("searchuri", $_GET))
	{
		$searchuri = $_GET['searchuri'];
	}
	if(array_key_exists("searchfield", $_GET))
	{
		$searchfield = $_GET['searchfield'];
	}

	if(count($_GET) == 0)
	{
		renderPage($f3, "search");
		exit();
	}

	$f3->set('TEMP', '/tmp');
	$f3->set('menu_file', $f3->get('brand_file'));
	$f3->set('page_title','Bus Search');

	renderClarification($f3, $sourceuri, $searchuri, $searchfield, $format);
}

function mobileErrorPage($f3)
{
}

function mobileRoutePage($f3, $params)
{
	function middle($val1, $val2)
	{
		if($val1 < $val2)
		{
			return ($val1 + (($val2 - $val1) / 2));
		} else {
			return ($val2 + (($val1 - $val2) / 2));
		}
	}

	$format = @$params['format'];
	$source_uri = "http://id.southampton.ac.uk/bus-stop/" . preg_replace("/[^A-Za-z0-9]/", "", @$_GET['begin']);
	$dest_uri = "http://id.southampton.ac.uk/bus-stop/" . preg_replace("/[^A-Za-z0-9]/", "", @$_GET['end']);
	$route_uri = "http://id.southampton.ac.uk/bus-route/" . $_GET['route'];
	$search = new Search($source_uri, $dest_uri);
	$title = $search->searchTitle($route_uri);
	$route_id = "";
	if(strlen(stristr($title, "|")) > 0)
	{
		$route_id = preg_replace("/([^\\|]+)\\|(.*)/", "$1", $title);
	}

	if(strcmp($format, "json") == 0)
	{
		header("Content-type: application/json");

		print($search->toJson());
		exit();
	}

	$stops = getStopsOnRoute($_GET['route'], "http://id.southampton.ac.uk/bus-stop/" . $_GET['begin'], "http://id.southampton.ac.uk/bus-stop/" . $_GET['end']);
	if(count($stops) <= 2)
	{
		mobileErrorPage($f3);
		exit();
	}
	$first_stop = new BusStop(preg_replace("|(.*)/([^/]*)|", "$2", $stops[0]['uri']), $f3->get('sparql_endpoint'));
	$last_stop = new BusStop(preg_replace("|(.*)/([^/]*)|", "$2", $stops[(count($stops) - 1)]['uri']), $f3->get('sparql_endpoint'));
	$lat = (float) $_GET['lat'];
	$lon = (float) $_GET['lon'];

	$f3->set('TEMP', '/tmp');
	$f3->set('menu_file', $f3->get('mobile_brand_file'));
	$f3->set('page_title','Bus Route');
	$content = '<div data-role="page" id="page1"><div data-theme="a" data-role="header">
                <h3 id="header">' . $title . '</h3>
                <div data-role="navbar" id="navbar">
                     <ul>
                        <li><a href="#" class="ui-btn-active routepageselect" data-tab-class="tab1">Stops</a></li>
                        <li><a href="#" class="routepageselect" data-tab-class="tab2">Maps</a></li>
                        <li><a href="#" class="routepageselect" data-tab-class="tab3">Timetable</a></li>
                    </ul>
                </div>
            </div>
            <div id="route_id" style="display: none;">' . trim($route_id) . '</div>
            <div data-role="content" id="pagecontent">';

	$content .= "<div id=\"page_tab1\">";
	$content .= "<ul data-role=\"listview\" data-divider-theme=\"b\" data-inset=\"true\">";
	foreach($stops as $stop)
	{
		$content .= "<li data-theme=\"c\"><a href=\"/bus-stop-mobile/" . preg_replace("|(.*)/([^/]*)|", "$2", $stop['uri']) . ".html\" data-transition=\"slide\">" . $stop['name'] . "</a></li>";
	}
	$content .= "</ul>";
	$content .= "</div>";

	$content .= "<div id=\"page_tab2\" style=\"display: none;\">";
	$first_ll = $first_stop->latLon();
	$last_ll = $last_stop->latLon();
	$dest_ll = getLocation($_GET['uri']);

	$content .= "<h3 align=\"center\">Current location to bus</h3><div style=\"display: block; width: 100%; height: 300px; background-position: center; background-repeat: no-repeat; background-image:url(http://bus.southampton.ac.uk/graphics/staticmaplite/staticmap.php?center=" . middle($first_ll['lat'], $lat) . "," . middle($first_ll['lon'], $lon) . "&zoom=16&size=720x300&markers=" . $lat . "," . $lon . ",ol-marker-green|" . $first_ll['lat'] . "," . $first_ll['lon'] . ",ol-marker);\"></div>";
	$content .= "<h3 align=\"center\">Bus to destination</h3><div style=\"display: block; width: 100%; height: 300px; background-position: center; background-repeat: no-repeat; background-image:url(http://bus.southampton.ac.uk/graphics/staticmaplite/staticmap.php?center=" . middle($last_ll['lat'], $dest_ll['lat']) . "," . middle($last_ll['lon'], $dest_ll['lon']) . "&zoom=16&size=720x300&markers=" . $dest_ll['lat'] . "," . $dest_ll['lon'] . ",ol-marker-green|" . $last_ll['lat'] . "," . $last_ll['lon'] . ",ol-marker);\">";
	$content .= "</div></div>";

	$content .= "<div id=\"page_tab3\" style=\"display: none;\">";
	$content .= "<div id=\"mobile_live_times\"></div>";
	$content .= "</div>";

	$content .= "</div>";

	$f3->set('page_content', $content);
	$template = new Template;
	echo $template->render($f3->get('mobile_brand_file'));
}

function mobileSearchPage($f3, $params)
{
	function mobile_route_search($a, $b)
	{
		$da = (int) $a['dist'];
		$db = (int) $b['dist'];
		if($da < $db)
		{
			return -1;
		}
		if($da > $db)
		{
			return 1;
		}
		$ca = count($a['stops']);
		$cb = count($b['stops']);
		if($ca < $cb)
		{
			return -1;
		}
		if($ca > $cb)
		{
			return 1;
		}
		return 0;
	}

	@$lat = (float) $_GET['lat'];
	@$lon = (float) $_GET['lon'];
	@$target_uri = $_GET['uri'];

	if(strlen($target_uri) == 0)
	{
		exit();
	}

	if(($lat == 0) | ($lon == 0))
	{
		$lat = 50.93626;
		$lon = -1.39684;
	}

	$routes = findBusRoutes($lat, $lon, $target_uri);
	$neareststops_target = nearestStops($target_uri);
	$neareststops = nearestBusStops($lat, $lon);

	if(count($routes) > 0)
	{
		usort($routes, "sortByDistance");

		$best_get_on_stop = $routes[0]['get_on']['uri'];
		$stop_lat = (float) $routes[0]['get_on']['lat'];
		$stop_lon = (float) $routes[0]['get_on']['lon'];

		$routes2 = $routes;
		$routes = array();
		foreach($routes2 as $route)
		{
			if(strcmp($best_get_on_stop, $route['get_on']['uri']) == 0)
			{
				$routes[] = $route;
			}
		}
		if($stop_lat > $lat)
		{
			$clat = (($stop_lat - $lat) / 2) + $lat;
		}
		else
		{
			$clat = (($lat - $stop_lat) / 2) + $stop_lat;
		}
		if($stop_lon > $lon)
		{
			$clon = (($stop_lon - $lon) / 2) + $lon;
		}
		else
		{
			$clon = (($lon - $stop_lon) / 2) + $stop_lon;
		}
		$mapcentre = $clat . "," . $clon;
	} else {
		$mapcentre = $lat . "," . $lon;
	}

	$mapurl = "https://maps.googleapis.com/maps/api/staticmap?size=288x200&scale=1&markers=" . urlencode("color:red|" . $stop_lat . "," . $stop_lon) . "&markers=" . urlencode("color:green|" . $lat . "," . $lon) . "&sensor=false";

	$f3->set('TEMP', '/tmp');
	$f3->set('menu_file', $f3->get('mobile_brand_file'));
	$f3->set('page_title','Bus Search');
	$content = '<div data-role="page" id="page1"><div data-theme="a" data-role="header">
                <h3 id="header">Bus Search</h3>
            </div>
            <div data-role="content" id="pagecontent">';

	$old_routes = $routes;
	$routes = array();
	foreach($old_routes as $route)
	{
		$route['number'] = $route['id'];
		$route['id'] = preg_replace("|(.*)/([^/]*)|", "$2", $route['uri']);
		$route['stops'] = getStopsOnRoute($route['id'], $route['get_on']['uri'], $route['get_off']['uri']);
		$routes[] = $route;
	}
	usort($routes, "mobile_route_search");
	$done = array();
	$content .= "<ul data-role=\"listview\" data-inset=\"true\">";
	$lastinfo = "";
	foreach($routes as $route)
	{
		if(in_array($route['id'], $done))
		{
			continue;
		}
		$info = $route['number'] . $route['get_on']['uri'] . $route['get_off']['uri'];
		if(strcmp($info, $lastinfo) == 0)
		{
			continue;
		}
		$content .= "<li><a href=\"/search/mobile-route.html?uri=" . urlencode($_GET['uri']) . "&begin=" . preg_replace("|(.*)/([^/]*)|", "$2", $route['get_on']['uri']) . "&end=" . preg_replace("|(.*)/([^/]*)|", "$2", $route['get_off']['uri']) . "&route=" . $route['id'] . "&lat=" . $lat . "&lon=" . $lon . "\">";
		$content .= "<h3>" . $route['number'] . " from " . $route['get_on']['title'] . "</h3>";
		$content .= "<p><strong>Alight at " . $route['get_off']['title'] . "</strong></p>";
		$content .= "<p>Total walking distance: " . $route['dist'] . "m (approx)</p>";
		$content .= "<p>Route stops: " . (count($route['stops']) - 1) . "</p>";
		//$content .= "<p class=\"ui-li-aside\"><strong>" . $route['number'] . "</strong></p>";
		$content .= "</a></li>";
		$done[] = $route['id'];
		$lastinfo = $info;
	}
	$content .= "</ul>";

	$content .= "</div></div>";

	$f3->set('page_content', $content);
	$template = new Template;
	echo $template->render($f3->get('mobile_brand_file'));

}

function nearbyStops($uri)
{
	$areas = getAreas();
	foreach($areas as $area)
	{
		if(strcmp($area['uri'], $uri) == 0)
		{
			return($area['stops']);
		}
	}
	$stops = nearestStops($uri);
	$ret = array();
	foreach($stops as $stop)
	{
		$ret[] = preg_replace("|(.+)/([^/]*)|", "$2", $stop['uri']);
	}
	return($ret);
}

function renderSearchResults($f3, $source_uri, $dest_uri, $format)
{
	$search = new Search($source_uri, $dest_uri);

	$stops = $search->getStops();
	$stops1 = $stops['start'];
	$stops2 = $stops['end'];
	$routes = $search->getRoutes();

	$query = "SELECT * WHERE { <" . $source_uri . "> <http://www.w3.org/2000/01/rdf-schema#label> ?source . <" . $dest_uri . "> <http://www.w3.org/2000/01/rdf-schema#label> ?dest . } LIMIT 1";
	$result = sparql_get($f3->get('sparql_endpoint'), $query);
	if(count($result) > 0)
	{
		$r = $result[0];
		//$f3->set('page_title', $r['source'] . " to " . $r['dest']);
		$f3->set('page_title', "Buses to " . $r['dest']);
	}

	if(strcmp($format, "json") == 0)
	{
		print($search->toJson());
		exit();

		$routes['stops'] = array_merge($stops1, $stops2);
		print(json_encode($routes));
		exit();
	}

	if(count($routes) == 0)
	{
		renderRouteless($f3);
		exit();
	}

	foreach($routes as $uri => $route)
	{
		//$content .= $route['operator'] . " " . $route['id'] . "<br>";
	}

	//$content .= "<h2>Live times</h2>";

	$f3->set('page_content', "");
	$f3->set('search_results', $routes);
	$f3->set('search_start', $stops1);
	$f3->set('search_end', $stops2);
	$f3->set('page_object', $search);
	$f3->set('page_template', "./templates/searchresults.html");
	$template = new Template;
	echo $template->render($f3->get('brand_file'));
}

function renderRouteless($f3)
{
	$content = "";

	$content .= "<h3>No routes found</h3>";
	$content .= "<p>We're confident we know where you want to go, but no buses go there!</p>";
	$content .= "<p>It's possible we made a mistake. It may be worth trying your search again. If not, we only show direct buses through this service, it may be worth consulting the <a href=\"/bus-routes.html\">route list</a> to find a place you can change.</p>";
	$content .= "<p></p>";
	$content .= "<p></p>";

	//$f3->set('page_title', "Buses to ");
	$f3->set('page_content', $content);
	$template = new Template;
	echo $template->render($f3->get('brand_file'));
}

function renderClarification($f3, $source_uri, $dest_uri, $query, $format)
{
	$results = array();
	if((strlen($dest_uri) == 0) & (strlen($query) > 0))
	{
		$results = southamptonThingSearch($query);
	}
	if(count($results) == 1)
	{
		$dest_uri = $results[0]['uri'];
	}
	if((strlen($source_uri) > 0) & (strlen($dest_uri) > 0))
	{
		renderSearchResults($f3, $source_uri, $dest_uri, $format);
		exit();
	}

	if(strcmp($format, "html") != 0)
	{
		$f3->error(404);
		exit();
	}

	$content = "";

	if(strlen($dest_uri) == 0)
	{
		$results = southamptonThingSearch($query);
		if(count($results) == 0)
		{
			$content .= "<h3>No Search Results</h3>";
			$content .= "<p>Apologies, but we can't work out where you're trying to go!</p>";
			$content .= "<p>Sometimes the trading name of an establishment can be slightly different to the name by which it is commonly known. Please refine your search before trying again.</p>";
			$content .= "<p></p>";
		}
	}

	if(strlen($content) == 0)
	{

		$content .= "<p>We need a little bit more information to continue.</p>";
		$content .= "<form action=\"/search/finder.html\" method=\"GET\">";

		if(strlen($source_uri) == 0)
		{
			$areas = getAreas();

			$content .= "<h2>Your Location</h2>";
			$content .= "<p>Where you currently are, or the location from where you need to get a bus.</p>";
			$content .= "<select id=\"sourceuri\" name=\"sourceuri\" size=\"1\">";
			foreach($areas as $area)
			{
				$content .= "<option";
				if(strcmp($area['uri'], "http://bus.southampton.ac.uk/area/highfield.html") == 0)
				{
					$content .= " selected=\"selected\"";
				}
				$content .= " value=\"" . $area['uri'] . "\">" . $area['title'] . "</option>";
			}
			$content .= "</select>";
		} else {
			$content .= "<input type=\"hidden\" id=\"sourceuri\" name=\"sourceuri\" value=\"" . $source_uri . "\">";
		}

		if(strlen($dest_uri) == 0)
		{
			$content .= "<h2>Your Destination</h2>";
			$content .= "<p>Your search query was ambiguous, please select from the following options.</p>";
			$content .= "<div class=\"dest_select\">";
			$radio_id = 0;
			foreach($results as $result)
			{
				//print($result['title'] . " - " . $result['uri'] . "<br>");
				$content .= "<div class=\"dest_option\"><input id=\"searchuri_" . $radio_id . "\" type=\"radio\" name=\"searchuri\" value=\"" . $result['uri'] . "\"><label for=\"searchuri_" . $radio_id . "\">" . $result['title'] . "</label></div>";
				$radio_id++;
			}
			$content .= "</div>";
		} else {
			$content .= "<input type=\"hidden\" id=\"searchuri\" name=\"searchuri\" value=\"" . $dest_uri . "\">";
		}

		$content .= "<input type=\"submit\">";

		$content .= "</form>";
	}

	$f3->set('page_content', $content);
	$template = new Template;
	echo $template->render($f3->get('brand_file'));
}

function autocompleteJson($f3, $params)
{
	header("Content-type: text/plain");

	@$query = $params['query'];
	if(strlen($query) == 0)
	{
		$results = array();
	} else {
		$results = southamptonThingSearch($query);
		if((array_key_exists("lat", $_GET)) | (array_key_exists("lon", $_GET)))
		{
			usort($results, "locSort");
		}
	}
	$r = array();
	foreach($results as $res)
	{
		$item = array();
		$item['id'] = $res['uri'];
		$item['label'] = $res['title'];
		$item['value'] = $res['title'];
		$r[] = $item;
	}

	print(json_encode($r));
	exit();
}

function mobileAutoCompleteJson($f3, $params)
{
	header("Content-type: text/plain");

	if(array_key_exists("q", $_GET))
	{
		$query = $_GET['q'];
	} else {
		$query = "";
	}

	$results = southamptonThingSearch($query);

	if((array_key_exists("lat", $_GET)) & (array_key_exists("lon", $_GET)))
	{
                usort($results, "locSort");
	}

	print(json_encode($results));
}
