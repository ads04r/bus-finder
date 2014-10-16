<?php

function renderTile($f3, $params)
{
	$z = $params['z'];
	$x = $params['x'];
	$y = $params['y'];


	$url = str_replace("[Z]", $z, str_replace("[X]", $x, str_replace("[Y]", $y, $f3->get('map_url'))));
	$parse = explode("://", $url, 2);
	if(count($parse) < 2)
	{
		$f3->error(404);
		exit();
	}
	$cache = "./graphics/staticmaplite/cache/tiles/" . $parse[1];
	$cachedir = preg_replace("|(.*)/([^/]+)|", "$1", $cache);
	if(!(is_dir($cachedir)))
	{
		mkdir($cachedir, 0755, true);
	}
	if(file_exists($cache))
	{
		$png = file_get_contents($cache);
	} else {
		$png = file_get_contents($url);
		if(is_dir($cachedir))
		{
			$fp = fopen($cache, "w");
			fwrite($fp, $png);
			fclose($fp);
		}
	}

	header("Cache-control: max-age=86400"); 
	header("Content-type: image/png");
	print($png);
	exit();
}
