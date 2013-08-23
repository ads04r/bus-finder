<?php

function renderPage($f3, $page)
{
	$filename = "./docs/" . $page . ".php";
	if(!(file_exists($filename)))
	{
		$f3->error(404);
		exit();
	}
	$f3->set('TEMP', '/tmp');
	$f3->set('menu_file', $f3->get('brand_file'));
	$f3->set('page_title','Southampton Bus Information');
	ob_start();
	include($filename);
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
	if(strcmp($format, "kml") == 0)
	{
		header("Content-type: application/xml");
		print($bs->toKml());
		exit();
	}
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
	$id = $params['routecode'];

	$f3->set('TEMP', '/tmp');
	$f3->set('page_title', "Route");
	$f3->set('page_content', '');
	//$f3->set('page_object', $area);
	$f3->set('page_template', './templates/route.html');
	$template = new Template;
	echo $template->render($f3->get('brand_file'));
}

function busArea($f3, $params)
{
	$area = new Area($params['areaid'], $f3->get('sparql_endpoint'));
	@$format = $params['format'];
	if(strcmp($format, "rdf") == 0)
	{
		header("Content-type: application/rdf+xml");
		print($area->toRdf());
		exit();
	}
	if(strcmp($format, "ttl") == 0)
	{
		header("Content-type: text/plain");
		print($area->toTtl());
		exit();
	}
	if(strcmp($format, "json") == 0)
	{
		header("Content-type: text/plain");
		print($area->toJson());
		exit();
	}
	$f3->set('TEMP', '/tmp');
	$f3->set('page_title',$area->label());
	$f3->set('page_content', '');
	$f3->set('page_object', $area);
	$f3->set('page_template', './templates/area.html');
	$template = new Template;
	echo $template->render($f3->get('brand_file'));
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
