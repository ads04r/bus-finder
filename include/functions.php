<?php

$sparql_endpoint = "http://sparql.data.southampton.ac.uk/";

function getLabel($uri)
{
	global $sparql_endpoint;

	$sparql = "
SELECT ?label WHERE {
    <" . $uri . "> <http://www.w3.org/2000/01/rdf-schema#label> ?label .
} LIMIT 1
	";
        $result = sparql_get(   $sparql_endpoint, $sparql);
	$r = array();
	if(count($result) > 0)
	{
		return($result[0]['label']);
	}

	return("");
}

function getLocation($uri)
{
	global $sparql_endpoint;

	$sparql = "
SELECT ?lat ?lon WHERE {
    <" . $uri . "> <http://www.w3.org/2003/01/geo/wgs84_pos#lat> ?lat .
    <" . $uri . "> <http://www.w3.org/2003/01/geo/wgs84_pos#long> ?lon .
} LIMIT 10
	";
        $result = sparql_get(   $sparql_endpoint, $sparql);
	$r = array();
	if(count($result) > 0)
	{
		$r['lat'] = $result[0]['lat'];
		$r['lon'] = $result[0]['lon'];
	}

	return($r);
}

function getStopsOnRoute($route, $start, $end)
{
	global $sparql_endpoint;
	
	$sparql = "
SELECT DISTINCT ?route_number ?order ?bus_stop ?stop_name WHERE {
    <http://id.southampton.ac.uk/bus-route/" . $route . "> <http://www.w3.org/2004/02/skos/core#notation> ?route_number .
    <http://id.southampton.ac.uk/bus-route/" . $route . "> <http://vocab.org/transit/terms/routeStop> ?stopid .
    ?stopid <http://vocab.org/transit/terms/sequence> ?order .
    ?stopid <http://vocab.org/transit/terms/stop> ?bus_stop .
    ?bus_stop <http://www.w3.org/2000/01/rdf-schema#label> ?stop_name .
} ORDER BY ASC(?order)
	";
        $result = sparql_get(   $sparql_endpoint, $sparql);
	$r = array();

	$started = 0;
	$finished = 0;
	if(strlen($start) == 0)
	{
		$started = 1;
	}

	if( isset($result) )
	{
		foreach($result as $stop) {
			$uri = $stop['bus_stop'];
			if(strcmp($uri, $start) == 0)
			{
				$started = 1;
			}
			if(($started == 1) & ($finished == 0))
			{
				$item = array();
				$item['uri'] = $uri;
				$item['name'] = $stop['stop_name'];
				$item['route'] = $stop['route_number'];
				$r[] = $item;
			}
			if(strcmp($uri, $end) == 0)
			{
				$finished = 1;
			}
		}
	}
	return($r);
}

function getBuildingBusRoutes($uri)
{
	$r = "<table>";

        $routes_airport = findBusRoutes(50.94977, -1.36359, $uri);
	$routes_station = findBusRoutes(50.90715, -1.41315, $uri);
        if(count($routes_station) > 0)
        {
                usort($routes_station, "sortByDistance");
                $r .= "<tr>";
                $r .= "<td><a style=\"\" href=\"" . $routes_station[0]['uri'] . "\">" . $routes_station[0]['id'] . "</a></td>";
                $r .= "<td>From Central Station</td>";
                $r .= "</tr>";
        }
        if(count($routes_airport) > 0)
        {
                usort($routes_airport, "sortByDistance");
                $r .= "<tr>";
                $r .= "<td class=\"busnumber\"><a style=\"\" href=\"" . $routes_airport[0]['uri'] . "\">" . $routes_airport[0]['id'] . "</a></td>";
                $r .= "<td>From Southampton Airport</td>";
                $r .= "</tr>";
        }

        $r .= "</table>";

	return($r);
}

function idFromUri($uri)
{
        $parse = explode("/", trim($uri, "/"));
        $c = count($parse);
        @$r = $parse[($c - 1)];

        return($r);
}

function nearestStops($uri)
{
        $cachefile = "./neareststopcache.json";
        $r = array();
        if(file_exists($cachefile))
        {
                $cache = json_decode(file_get_contents($cachefile), true);
        } else {
                $cache = array();
        }
        if(array_key_exists($uri, $cache))
        {
                $r = $cache[$uri];
        } else {
                $ll = entityPosition($uri);
                if(count($ll) > 0)
                {
                        $lat = $ll['lat'];
                        $lon = $ll['lon'];
                        $stopinfo = nearestBusStops($lat, $lon);
                        $cache[$uri] = $stopinfo;
                        if(@$fp = fopen($cachefile, "w"))
                        {
                                fwrite($fp, json_encode($cache));
                                fclose($fp);
                        }
                        $r = $stopinfo;
                }
        }

        return($r);
}

function crossRoutes($stops_from, $stops_to)
{
	global $sparql_endpoint;
	$routes = array();
	$stops = array();

	foreach($stops_from as $s1)
	{
		$from_uri = "http://id.southampton.ac.uk/bus-stop/" . $s1;
		foreach($stops_to as $s2)
		{
			$to_uri = "http://id.southampton.ac.uk/bus-stop/" . $s2;
			$query = "

				SELECT ?route ?routecode ?operatorname WHERE {

				  ?routestopsource <http://id.southampton.ac.uk/ns/busStoppingAt> ?source .
				  ?route <http://vocab.org/transit/terms/routeStop> ?routestopsource .
				  ?routestopsource <http://vocab.org/transit/terms/sequence> ?seqsource .

				  ?routestopdest <http://id.southampton.ac.uk/ns/busStoppingAt> ?dest .
				  ?route <http://vocab.org/transit/terms/routeStop> ?routestopdest .
				  ?routestopdest <http://vocab.org/transit/terms/sequence> ?seqdest .

				  ?route <http://www.w3.org/2004/02/skos/core#notation> ?routecode .
				  ?route <http://id.southampton.ac.uk/ns/busRouteOperator> ?operator .
				  ?operator <http://www.w3.org/2000/01/rdf-schema#label> ?operatorname .

				  FILTER ( ?source = <" . $from_uri . "> )
				  FILTER ( ?dest = <" . $to_uri . "> )
				  FILTER ( ?seqsource < ?seqdest )
				}

			";
		        $result = sparql_get($sparql_endpoint, $query);
			foreach($result as $row)
			{
				$uri = $row['route'];
				if(!(array_key_exists($uri, $routes)))
				{
					$item = array();
					$item['id'] = $row['routecode'];
					$item['operator'] = $row['operatorname'];
					$item['uri'] = $uri;
					$routes[$uri] = $item;
					if(!(in_array($s1, $stops)))
					{
						$stops[] = $s1;
					}
					if(!(in_array($s2, $stops)))
					{
						$stops[] = $s2;
					}
				}
			}

		}
	}

	$ret = array();
	$ret['routes'] = $routes;
	$ret['stops'] = $stops;
	return($ret);
}

function findBusRoutes($lat, $lon, $target_uri)
{
	$neareststops_target = nearestStops($target_uri);
	$neareststops = nearestBusStops($lat, $lon);

	$routes = array();

	foreach($neareststops as $s1)
	{
	        foreach($neareststops_target as $s2)
	        {
	                $routes1 = $s2['routes'];
	                $routes2 = $s1['routes'];
	                foreach($routes1 as $r1)
	                {
	                        foreach($routes2 as $r2)
	                        {
	                                if(strcmp($r1['uri'], $r2['uri']) == 0)
	                                {
	                                        if($r1['order'] > $r2['order'])
	                                        {
	                                                $item = $r1;
	                                                $item['dist'] = $s1['dist'] + $s2['dist'];
	                                                $item['get_on'] = $s1;
	                                                $item['get_off'] = $s2;
	                                                unset($item['get_on']['routes']);
	                                                unset($item['get_off']['routes']);

	                                                $routes[] = $item;
	                                        }
	                                }
	                        }
	                }
	        }
	}

	return($routes);
}

function entityPosition($uri)
{
	global $sparql_endpoint;
	
    $sparql = "
SELECT DISTINCT * WHERE {
    <" . $uri . "> <http://www.w3.org/2003/01/geo/wgs84_pos#lat> ?lat .
    <" . $uri . "> <http://www.w3.org/2003/01/geo/wgs84_pos#long> ?lon .
} LIMIT 5000
        ";

    $result = sparql_get(   $sparql_endpoint, $sparql);
	$r = array();

                if( isset($result) )
		{
                        $r = array();
                        foreach($result as $info)
                        {
				$item = array();
				$item['lat'] = (float) $info['lat'];
				$item['lon'] = (float) $info['lon'];
				$r = $item;
                        }
                }

        return($r);

}

function sortByDistance($a, $b)
{
	if($a['dist'] == $b['dist'])
	{
		return 0;
	}
	else
	{
		if($a['dist'] < $b['dist'])
		{
			return -1;
		}
		else
		{
			return 1;
		}
	}
}

function busRoutes($stopuri)
{
	global $sparql_endpoint;
	
	$sparql = "
SELECT DISTINCT ?o ?v ?seq WHERE {
  ?s <http://id.southampton.ac.uk/ns/busStoppingAt> <" . $stopuri . "> .
  ?s <http://id.southampton.ac.uk/ns/inBusRoute> ?o .
  ?o <http://www.w3.org/2004/02/skos/core#notation> ?v .
  ?s <http://vocab.org/transit/terms/sequence> ?seq .
}
	";

//	print("\n\n" . $sparql . "\n\n");

        $result = sparql_get(   $sparql_endpoint, $sparql);
	$r = array();

                if( isset($result) )
		{
                        foreach($result as $info)
                        {
                                $item = array();
				$item['id'] = $info['v'];
				$item['uri'] = $info['o'];
				$item['order'] = (int) $info['seq'];
                               	$r[] = $item;
                        }
                }

	return($r);
}

function nearestBusStops($lat, $lon)
{
	global $sparql_endpoint;
	
    $sparql = "
SELECT DISTINCT * WHERE {
    ?uri <http://www.w3.org/2000/01/rdf-schema#label> ?title .
    ?uri a <http://transport.data.gov.uk/def/naptan/BusStop> .
    ?uri <http://www.w3.org/2003/01/geo/wgs84_pos#lat> ?lat .
    ?uri <http://www.w3.org/2003/01/geo/wgs84_pos#long> ?lon .
} LIMIT 5000
        ";

    $result = sparql_get(   $sparql_endpoint, $sparql);
	$r = array();

                if( !isset($result) )
                {
                        $r = array();
                } else {
                        $r = array();
                        foreach($result as $info)
                        {
                                $item = $info;
				$dist = ((int) (distance(((float) $info['lat']), ((float) $info['lon']), $lat, $lon, 'K') * 1000));
				$item['dist'] = $dist;
				$item['uri'] = str_replace("id.data-dev.ecs.soton.ac.uk", "id.southampton.ac.uk", $item['uri']);
                               	$r[] = $item;
                        }
                }

	usort($r, "sortByDistance");
    $rr = array_slice($r, 0, 15);
	$r = array();
	foreach($rr as $item)
	{
		$item['routes'] = busRoutes($item['uri']);
		$r[] = $item;
	}
	return($r);
}


function distance($lat1, $lon1, $lat2, $lon2, $unit='M')
{

	$theta = $lon1 - $lon2;
	$dist = sin(deg2rad($lat1)) * sin(deg2rad($lat2)) +  cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad($theta));
	$dist = acos($dist);
	$dist = rad2deg($dist);
	$miles = $dist * 60 * 1.1515;
	$unit = strtoupper($unit);

	if ($unit == "K")
	{
		return ($miles * 1.609344);
	} else if ($unit == "N") {
		return ($miles * 0.8684);
	} else {
		return $miles;
	}
}

function locSort($a, $b)
{
	$lat = 0;
	$lon = 0;
	@$lat = (float) $_GET['lat'];
	@$lon = (float) $_GET['lon'];
	if(($lat == 0) & ($lon == 0))
	{
		@$lat = (float) $_POST['lat'];
		@$lon = (float) $_POST['lon'];
	}
	$lata = (float) $a['lat'];
	$latb = (float) $b['lat'];
	$lona = (float) $a['lon'];
	$lonb = (float) $b['lon'];
	$da = distance($lat, $lon, $lata, $lonb);
	$db = distance($lat, $lon, $latb, $lonb);

	if($da == $db)
	{
		return 0;
	} else {
		if($da < $db)
		{
			return -1;
		} else {
			return 1;
		}
	}
}

function getAreas()
{
	$json = json_decode(file_get_contents("./config/startpoints.json"), true);
	$places = array();
	foreach($json as $id => $place)
	{
		@$stops = $place['stops'];
		if(!(is_array($stops)))
		{
			continue;
		}
		if(count($stops) == 0)
		{
			continue;
		}
		$place['url'] = "/area/" . $id . ".html";
		$places[] = $place;
	}
	return($places);
}
