<?php

header("Content-type: text/plain");
include "./include/datasearch.php";
include "./include/functions.php";

@$query = $_GET['term'];
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
