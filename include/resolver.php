<?php

function resolver($f3)
{
	$req = $f3->get('SERVER');
	$accept = explode(",", $req['HTTP_ACCEPT']);
	$uri = $f3->get('URI');
	foreach($accept as $type)
	{
		if(strcmp($type, "text/html") == 0)
		{
			header("Location: " . $uri . ".html", true, 303);
			exit();
		}
		if(strcmp($type, "text/plain") == 0)
		{
			header("Location: " . $uri . ".csv", true, 303);
			exit();
		}
		if((strcmp($type, "text/turtle") == 0) | (strcmp($type, "application/x-turtle") == 0))
		{
			header("Location: " . $uri . ".ttl", true, 303);
			exit();
		}
		if(strcmp($type, "application/rdf+xml") == 0)
		{
			header("Location: " . $uri . ".rdf", true, 303);
			exit();
		}
	}
}
