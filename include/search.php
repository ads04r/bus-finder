<?php

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
			foreach($results as $result)
			{
				//print($result['title'] . " - " . $result['uri'] . "<br>");
				$content .= "<input id=\"searchuri\" type=\"radio\" name=\"searchuri\" value=\"" . $result['uri'] . "\">" . $result['title'];
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

