<?php

/*
UNILINK redirect function
=========================
This function, and one line of code in index.php, is here to abuse the fact
that all Unilink bus services start with the letter U. So now, users of the
Unilinks can simply enter bus.soton.ac.uk/U1C to get the route page for the
U1C (for example) without the need to remember or bookmark the URI.
*/

function getUnilinkUri($service, $sparql_endpoint)
{
	$query = "

select * where {
	?uri <http://www.w3.org/1999/02/22-rdf-syntax-ns#type> <http://vocab.org/transit/terms/BusRoute> .
	?uri <http://www.w3.org/2004/02/skos/core#notation> ?service .
	FILTER ( str(?service) = '" . $service . "' )
}

	";
	$result = sparql_get($sparql_endpoint, $query);
	if(!(isset($result)))
	{
		return("");
	}
	foreach($result as $bus)
	{
		return($bus['uri']);
	}
	return("");
}

function unilinkRedirect($f3, $params)
{
	$service = "U" . $params['service'];
	$uri = getUnilinkUri($service, $f3->get('sparql_endpoint'));
	if(strlen($uri) > 0)
	{
		$f3->reroute($uri);
	}
	$f3->error(404);
}
