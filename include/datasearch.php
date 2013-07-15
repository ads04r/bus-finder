<?php

$sparql_endpoint = "http://sparql.data.southampton.ac.uk/";

function freeTextSearch($searchQuery)
{
	global $sparql_endpoint;

	$query = strtolower(preg_replace("/[^ a-zA-Z0-9]/", "", $searchQuery));
	if(strlen($query) > 3)
	{
		$regex = "";
		$c = strlen($query);
		$i = 0;
		for($i = 0; $i < $c; $i++)
		{
			$char = substr($query, $i, 1);
			if(strcmp($char, " ") == 0)
			{
				$regex .= "(.*)";
			} elseif(preg_match("/[a-z]/", $char) > 0)
			{
				$regex .= "[" . strtoupper($char) . $char . "]";
			} else {
				$regex .= $char;
			}
		}

		$sparql = "
PREFIX foaf: <http://xmlns.com/foaf/0.1/>
PREFIX rdfs: <http://www.w3.org/2000/01/rdf-schema#>
PREFIX skos: <http://www.w3.org/2004/02/skos/core#>

SELECT DISTINCT *
WHERE {
{?s foaf:name ?o} UNION {?s rdfs:label ?o} UNION {?s rdfs:comment ?o}
FILTER regex(?o, \"" . $regex . "\") }
ORDER BY ?o
LIMIT 100
		";
		$result = sparql_get($sparql_endpoint, $sparql);

		if( !isset($result) )
		{
			$r = array();
		} else {
			$r = array();
			foreach($result as $triple)
			{
				$item = array();
				$item['uri'] = $triple['s'];
				$item['label'] = $triple['o'];
				$r[] = $item;
			}
		}
	} else {
		$r = array();
	}

	return($r);

}

function southamptonThingSearch($searchQuery)
{
	global $sparql_endpoint;

	$query = strtolower(preg_replace("/[^ a-zA-Z0-9]/", "", $searchQuery));
	if(strlen($query) > 3)
	{
		$regex = "";
		$c = strlen($query);
		$i = 0;
		for($i = 0; $i < $c; $i++)
		{
			$char = substr($query, $i, 1);
			if(strcmp($char, " ") == 0)
			{
				$regex .= "(.*)";
			} elseif(preg_match("/[a-z]/", $char) > 0)
			{
				$regex .= "[" . strtoupper($char) . $char . "]";
			} else {
				$regex .= $char;
			}
		}

		$sparql = "
PREFIX foaf: <http://xmlns.com/foaf/0.1/>
PREFIX rdfs: <http://www.w3.org/2000/01/rdf-schema#>
PREFIX skos: <http://www.w3.org/2004/02/skos/core#>
PREFIX geo:  <http://www.w3.org/2003/01/geo/wgs84_pos#>

SELECT DISTINCT *
WHERE {
{
	?uri rdfs:label ?title .
	?uri geo:lat ?lat .
	?uri geo:long ?lon .
}
FILTER regex(?title, \"" . $regex . "\")
}
ORDER BY ?title
LIMIT 100
		";
		$result = sparql_get($sparql_endpoint, $sparql);

		if( !isset($result) )
		{
			$r = array();
		} else {
			$r = array();
			foreach($result as $triple)
			{
				$item = array();
				$item['uri'] = $triple['uri'];
				$item['title'] = $triple['title'];
				$item['lat'] = $triple['lat'];
				$item['lon'] = $triple['lon'];
				$r[] = $item;
			}
		}
	} else {
		$r = array();
	}

	return($r);
}
