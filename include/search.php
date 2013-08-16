<?php

function searchPage($f3, $params)
{
	$f3->set('TEMP', '/tmp');
	$f3->set('menu_file', $f3->get('brand_file'));
	$f3->set('page_title','Bus Search');

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

	renderClarification($f3, $sourceuri, $searchuri, $searchfield);
}

function renderSearchResults($f3, $source_uri, $dest_uri)
{
}

function renderClarification($f3, $source_uri, $dest_uri, $query)
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
		renderSearchResults($f3, $source_uri, $dest_uri);
		exit();
	}

	$content = "";

	$content .= "<p>We need a little bit more information to continue.</p>";
	$content .= "<form action=\"/search/submit.html\" method=\"GET\">";

	if(strlen($source_uri) == 0)
	{
		$content .= "<h2>Your Location</h2>";
		$content .= "<p>Where you currently are, or the location from where you need to get a bus.</p>";
		$content .= "<select id=\"sourceuri\" name=\"sourceuri\" size=\"1\">";
		$content .= "<option selected=\"selected\" value=\"http://id.southampton.ac.uk/site/1\">Highfield Campus</option>";
		$content .= "</select>";
	} else {
		$content .= "<input type=\"hidden\" id=\"sourceuri\" name=\"sourceuri\" value=\"" . $source_uri . "\">";
	}

	if(strlen($dest_uri) == 0)
	{
		$content .= "<h2>Your Destination</h2>";
		$content .= "<p>Your search query was ambiguous, please select from the following options.</p>";
	} else {
		$content .= "<input type=\"hidden\" id=\"searchuri\" name=\"searchuri\" value=\"" . $dest_uri . "\">";
	}

	$content .= "<input type=\"submit\">";

	$content .= "</form>";

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

