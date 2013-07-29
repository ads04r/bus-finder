<?php

$db = sparql_connect($f3->get('sparql_endpoint'));
if( !$db ) { print $db->errno() . ": " . $db->error(). "\n"; exit; }
$db->ns( "rdfs","http://www.w3.org/2000/01/rdf-schema#" );
$db->ns( "skos","http://www.w3.org/2004/02/skos/core#" );
$db->ns( "ns", "http://id.southampton.ac.uk/ns/" );
$db->ns( "soton", "http://id.southampton.ac.uk/ns/" );

$sparql = "
SELECT DISTINCT ?route ?label ?notation ?operator WHERE
{
        <http://id.southampton.ac.uk/dataset/bus-info> <http://rdfs.org/ns/void#dataDump> ?graph .
        GRAPH ?graph
        {
                ?route a <http://id.southampton.ac.uk/ns/BusRoute> .
                ?route rdfs:label ?label .
                ?route skos:notation ?notation .
                ?route soton:busRouteOperator ?op .
                ?op rdfs:label ?operator .
        }
}
ORDER BY ?operator ?notation
";

$result = $db->query( $sparql, "List of Bus Routes" );
if( !$result ) { print $db->errno() . ": " . $db->error(). "\n"; exit; }
$data = $result->fetch_all();

$f3->set('page_title', 'Southampton Bus Routes');


$lastoperator = "";
$lastnotation = "";
foreach( $data as $row )
{
        $operator = $row['operator'];
	$notation = $row['notation'];
	$uri = $row['route'];
	$routeid = preg_replace("|(.*)/([^/]*)$|", "$2", $uri);
        if(strcmp($operator, $lastoperator) != 0)
        {
		if(strlen($lastoperator) > 0)
		{
			print("</div>");
		}
                print("<h2>" . $operator . "</h2><div>");
        }
	print("<div class=\"busrouteentry\"><div class=\"routenumber\">");
	if(strcmp($notation, $lastnotation) != 0)
	{
		print($notation);
	}
	print("</div><div class=\"destination\"><a href=\"/bus-route/" . $routeid . ".html\">" . $row['label'] . "</a></div></div>");
        $lastoperator = $operator;
	$lastnotation = $notation;
}

print("</div>");
