<?php

function renderPage($f3, $page, $mobile=0)
{
	$filename = "./docs/" . $page . ".php";
	if(!(file_exists($filename)))
	{
		$f3->error(404);
		exit();
	}
	$f3->set('TEMP', '/tmp');
	if($mobile == 1)
	{
		$f3->set('menu_file', $f3->get('mobile_brand_file'));
	} else {
		$f3->set('menu_file', $f3->get('brand_file'));
	}
	$f3->set('page_title','Southampton Bus Information');
	ob_start();
	include($filename);
	$content = ob_get_contents();
	ob_end_clean();
	$f3->set('page_content', $content);
	$template = new Template;
	if($mobile == 1)
	{
		echo $template->render($f3->get('mobile_brand_file'));
	} else {
		echo $template->render($f3->get('brand_file'));
	}
}

function homePage($f3)
{
	renderPage($f3, "home");
}

function mobileHomePage($f3)
{
	renderPage($f3, "mobile", 1);
}

function otherPage($f3, $params)
{
	$page = $params['pagename'];
	if(strcmp(substr($page, -7), "-mobile") == 0)
	{
		renderPage($f3, $page, 1);
	} else {
		renderPage($f3, $page);
	}
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
	if(strcmp($format, "raw") == 0)
	{
		print($bs->toRaw());
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

function mobileBusStop($f3, $params)
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
	$f3->set('page_template', './templates/stop_mobile.html');
	$template = new Template;
	echo $template->render($f3->get('mobile_brand_file'));
}

function publicdisplayBusStop($f3, $params)
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
	$f3->set('page_template', './templates/stop_publicdisplay.html');
	$template = new Template;
	echo $template->render('./templates/publicdisplay.html');
}

function iframeBusStop($f3, $params)
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
	$f3->set('page_template', './templates/stop_iframe.html');
	$template = new Template;
	echo $template->render('./templates/iframe.html');
}

function busRoute($f3, $params)
{
	$id = $params['routecode'];
	$br = new BusRoute($id, $f3->get('sparql_endpoint'));
	@$format = $params['format'];
	if(strcmp($format, "kml") == 0)
	{
		header("Content-type: application/xml");
		print($br->toKml());
		exit();
	}
	if(strcmp($format, "rdf") == 0)
	{
		header("Content-type: application/rdf+xml");
		print($br->toRdf());
		exit();
	}
	if(strcmp($format, "ttl") == 0)
	{
		header("Content-type: text/plain");
		print($br->toTtl());
		exit();
	}
	if(strcmp($format, "json") == 0)
	{
		header("Content-type: text/plain");
		print($br->toJson());
		exit();
	}

	$f3->set('TEMP', '/tmp');
	$f3->set('page_title', $br->label());
	$f3->set('page_content', '');
	$f3->set('page_object', $br);
	$f3->set('page_template', './templates/route.html');
	$template = new Template;
	echo $template->render($f3->get('brand_file'));
}

function mobileBusRoute($f3, $params)
{
	$id = $params['routecode'];
	$br = new BusRoute($id, $f3->get('sparql_endpoint'));
	@$format = $params['format'];
	if(strcmp($format, "kml") == 0)
	{
		header("Content-type: application/xml");
		print($br->toKml());
		exit();
	}
	if(strcmp($format, "rdf") == 0)
	{
		header("Content-type: application/rdf+xml");
		print($br->toRdf());
		exit();
	}
	if(strcmp($format, "ttl") == 0)
	{
		header("Content-type: text/plain");
		print($br->toTtl());
		exit();
	}
	if(strcmp($format, "json") == 0)
	{
		header("Content-type: text/plain");
		print($br->toJson());
		exit();
	}

	$f3->set('TEMP', '/tmp');
	$f3->set('page_title', $br->label());
	$f3->set('page_content', '');
	$f3->set('page_object', $br);
	$f3->set('page_template', './templates/route_mobile.html');
	$template = new Template;
	echo $template->render($f3->get('mobile_brand_file'));
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

function publicdisplayBusArea($f3, $params)
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
	echo $template->render('./templates/publicdisplay.html');
}

function iframeBusArea($f3, $params)
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
	echo $template->render('./templates/iframe.html');
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
