<?php

function routeSort($a, $b)
{
	$op = strcmp($a['operator'], $b['operator']);
	if($op != 0)
	{
		return($op);
	}
	$not = strnatcmp($a['notation'], $b['notation']);
	if($not != 0)
	{
		return($not);
	}
	$desc = strnatcmp($a['label'], $b['label']);
	if($desc != 0)
	{
		return($desc);
	}
	if($a['stops'] < $b['stops'])
	{
		return 1;
	}
	if($a['stops'] > $b['stops'])
	{
		return -1;
	}
	return 0;
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
$data_old = (Array) $result->fetch_all();
$data = array();
foreach($data_old as $item)
{
                $query = "
                        SELECT ?uri WHERE {
                                ?uri <http://vocab.org/transit/terms/route> <" . $item['route'] . ">
                        }
                ";
                $result = $db->query($query);
                $data_info = $result->fetch_all();
                $item['stops'] = count($data_info);
                $data[] = $item;
}

$f3->set('page_title', 'Southampton Bus Routes');
usort($data, "routeSort");

print("<div  data-role=\"page\"> \n");
print("<div  data-role=\"header\">\n");
print("  <h1>Bus Routes</h1>\n");
print("</div>\n");
print("<div  data-role=\"content\">\n");

$lastoperator = "";
$lastnotation = "";
$lastlabel = "";
print("<ul data-role=\"listview\" data-divider-theme=\"d\">");
foreach( $data as $row )
{
        $operator = $row['operator'];
	$notation = $row['notation'];
	$label = $row['label'];
	$uri = $row['route'];
	if(strcmp($label, $lastlabel) == 0)
	{
		continue;
	}
	$routeid = preg_replace("|(.*)/([^/]*)$|", "$2", $uri);
        if(strcmp($operator, $lastoperator) != 0)
        {
                print("<li data-role=\"list-divider\">" . $operator . "</li>");
        }
	print("<li>");
	print("<a href=\"/bus-route-mobile/" . $routeid . ".html\">" . $notation . " - " . $row['label'] . "</a></li>");
        $lastoperator = $operator;
	$lastnotation = $notation;
	$lastlabel = $label;
}
print("</ul>");

print("</div>");
