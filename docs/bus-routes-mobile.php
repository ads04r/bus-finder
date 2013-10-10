<?php

function routeSort($a, $b)
{
	$op = strcmp($a['operator'], $b['operator']);
	if($op != 0)
	{
		return($op);
	}
	return(strnatcmp($a['notation'], $b['notation']));
}

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
";

$result = $db->query( $sparql, "List of Bus Routes" );
if( !$result ) { print $db->errno() . ": " . $db->error(). "\n"; exit; }
$data = (Array) $result->fetch_all();

$f3->set('page_title', 'Southampton Bus Routes');
usort($data, "routeSort");

print("<div  data-role=\"page\"> \n");
print("<div  data-role=\"header\">\n");
print("  <h1>Bus Routes</h1>\n");
print("</div>\n");
print("<div  data-role=\"content\">\n");

$lastoperator = "";
$lastnotation = "";
print("<ul data-role=\"listview\">");
foreach( $data as $row )
{
        $operator = $row['operator'];
	$notation = $row['notation'];
	$uri = $row['route'];
	$routeid = preg_replace("|(.*)/([^/]*)$|", "$2", $uri);
        if(strcmp($operator, $lastoperator) != 0)
        {
                print("<li data-role=\"list-divider\">" . $operator . "</li>");
        }
	print("<li>");
	print("<a href=\"/bus-route-mobile/" . $routeid . ".html\">" . $notation . " - " . $row['label'] . "</a></li>");
        $lastoperator = $operator;
	$lastnotation = $notation;
}
print("</ul>");

print("</div>");
