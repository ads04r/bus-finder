<?php

include_once("./include/datasearch.php");
include_once("./include/functions.php");

$f3 = require("fatfree/lib/base.php");
$f3->set('DEBUG', true);
$f3->set('page_template', "");

// Website settings

$f3->set('brand_file', "./templates/bus.html");
$f3->set('sparql_endpoint', "http://sparql.data.southampton.ac.uk/");

// Classes

include_once("./classes/BusStop.php");
include_once("./classes/Place.php");

// Render functions

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

function renderPage($f3, $page)
{
	$f3->set('TEMP', '/tmp');
	$f3->set('menu_file', $f3->get('brand_file'));
	$f3->set('page_title','Southampton Bus Information');
	ob_start();
	include("./docs/" . $page . ".php");
	$content = ob_get_contents();
	ob_end_clean();
	$f3->set('page_content', $content);
	$template = new Template;
	echo $template->render($f3->get('brand_file'));
}

function homePage($f3)
{
	renderPage($f3, "home");
}

function otherPage($f3, $params)
{
	renderPage($f3, $params['pagename']);
}

function busStop($f3, $params)
{
	$bs = new BusStop($params['stopcode'], $f3->get('sparql_endpoint'));
	@$format = $params['format'];
	if(strcmp($format, "rdf") == 0)
	{
		header("Content-type: application/rdf+xml");
		print($bs->toRdf());
		exit();
	}
	if(strcmp($format, "ttl") == 0)
	{
		header("Content-type: text/plain");
		print($bs->toTtl());
		exit();
	}
	if(strcmp($format, "json") == 0)
	{
		header("Content-type: text/plain");
		$maxrows = 5;
		if(array_key_exists("maxrows", $params))
		{
			$maxrows = (int) $params['maxrows'];
		}
		print($bs->toJson($maxrows));
		exit();
	}
	$f3->set('TEMP', '/tmp');
	$f3->set('page_title',$bs->label());
	$f3->set('page_content', '');
	$f3->set('page_object', $bs);
	$f3->set('page_template', './templates/stop.html');
	$template = new Template;
	echo $template->render($f3->get('brand_file'));
}

function busRoute($f3, $params)
{
}

function busArea($f3, $params)
{
}

function place($f3, $params)
{
	$fhrs = $params['fhrs'];

	$p = new Place($fhrs, $f3->get('sparql_endpoint'));
	@$format = $params['format'];
	if(strcmp($format, "rdf") == 0)
	{
		header("Content-type: application/rdf+xml");
		print($p->toRdf());
		exit();
	}
	if(strcmp($format, "ttl") == 0)
	{
		header("Content-type: text/plain");
		print($p->toTtl());
		exit();
	}
	if(strcmp($format, "json") == 0)
	{
		header("Content-type: text/plain");
		print($p->toJson());
		exit();
	}
	$f3->set('TEMP', '/tmp');
	$f3->set('page_title',$p->label());
	$f3->set('page_content', '');
	$f3->set('page_object', $p);
	$f3->set('page_template', './templates/place.html');
	$template = new Template;
	echo $template->render($f3->get('brand_file'));
}

// Routes

$f3->route("GET /", "homePage");
$f3->route("GET /area/@areaid.html", "busArea");
$f3->route("GET /bus-route/@routecode.@format", "busRoute");
$f3->route("GET /bus-stop/@stopcode.@format", "busStop");
$f3->route("GET /bus-stop/@stopcode.@format?max=@maxrows", "busStop");
$f3->route("GET /place/@fhrs.@format", "place");
$f3->route("GET /@pagename.html", "otherPage");
$f3->route("GET /search/autocomplete.json?term=@query", "autocompleteJson");
$f3->route("GET *", function($f3) { $f3->error(404); });

$f3->run();
