<?php

include_once("include/functions.php");

class Search
{
	private $hash;
	private $source_uri;
	private $dest_uri;
	private $routes;
	private $source_stops;
	private $dest_stops;
	private $source_label;
	private $dest_label;

	private $start_ll;
	private $end_ll;

	private $stop_names;

	public function uri()
	{
		return $this->dest_uri;
	}

	public function searchHash()
	{
		return $this->hash;
	}

	public function label()
	{
		return "Buses to " . $this->dest_label;
	}

	public function getRoutes()
	{
		return $this->routes;
	}

	public function getStartPointSelect()
	{
		$points = array();

		$item = array();
		$item['label'] = $this->source_label;
		$item['uri'] = $this->source_uri;

		$points[] = $item;

		$json = json_decode(file_get_contents("./config/startpoints.json"), true);
		foreach($json as $id=>$place)
		{
			$item = array();
			$uri = $place['uri'];
			if(strcmp($uri, $this->source_uri) == 0)
			{
				continue;
			}
			$item['uri'] = $uri;
			$item['label'] = $place['title'];
			$points[] = $item;
		}

		return($points);
	}

	public function getStops()
	{
		$stops = array();
		$stops['start'] = $this->source_stops;
		$stops['end'] = $this->dest_stops;

		return($stops);
	}

	public function subTitle()
	{
		return "From " . $this->source_label;
	}

	public function toJson()
	{
		$output = array();

		$output['routes'] = $this->routes;
		$output['stops'] = array();
		$output['stops']['start'] = $this->source_stops;
		$output['stops']['end'] = $this->dest_stops;
		$output['locations'] = array();
		$output['locations']['start'] = array();
		$output['locations']['end'] = array();
		$output['locations']['start']['uri'] = $this->source_uri;
		$output['locations']['end']['uri'] = $this->dest_uri;
		$output['locations']['start']['label'] = $this->source_label;
		$output['locations']['end']['label'] = $this->dest_label;
		$output['locations']['start']['lat'] = (float) $this->start_ll['lat'];
		$output['locations']['start']['lon'] = (float) $this->start_ll['lon'];
		$output['locations']['end']['lat'] = (float) $this->end_ll['lat'];
		$output['locations']['end']['lon'] = (float) $this->end_ll['lon'];
		$output['stopnames'] = $this->stop_names;

		return(json_encode($output));
	}

	public function getStopName($stop_id)
	{
		if(array_key_exists($stop_id, $this->stop_names))
		{
			return($this->stop_names[$stop_id]);
		}
		$stop_uri = "http://id.southampton.ac.uk/bus-stop/" . $stop_id;
		return(getLabel($stop_uri));
	}

	function __construct($start, $end)
	{
		function sortStart($a, $b)
		{
			if($a['start_dist'] < $b['start_dist'])
			{
				return -1;
			}
			if($a['start_dist'] > $b['start_dist'])
			{
				return 1;
			}
			return 0;
		}

		function sortEnd($a, $b)
		{
			if($a['end_dist'] < $b['end_dist'])
			{
				return -1;
			}
			if($a['end_dist'] > $b['end_dist'])
			{
				return 1;
			}
			return 0;
		}

		function sortSequence($a, $b)
		{
			if($a['sequence'] < $b['sequence'])
			{
				return -1;
			}
			if($a['sequence'] > $b['sequence'])
			{
				return 1;
			}
			return 0;
		}

		$this->stop_names = array();

		$this->source_uri = $start;
		$this->dest_uri = $end;
		$this->hash = md5($start . "\n" . $end);

		$cachefile = "./cache/" . $this->hash . ".json";
		/*
		if(file_exists($cachefile))
		{
			unlink($cachefile);
		}
		*/
		if(file_exists($cachefile))
		{
			$json = json_decode(file_get_contents($cachefile), true);

			$this->source_label = $json['locations']['start']['label'];
			$this->dest_label = $json['locations']['end']['label'];
			$this->source_stops = $json['stops']['start'];
			$this->dest_stops = $json['stops']['end'];
			$this->routes = $json['routes'];

			$this->start_ll = array("lat" => $json['locations']['start']['lat'], "lon" => $json['locations']['start']['lon']);
			$this->end_ll = array("lat" => $json['locations']['end']['lat'], "lon" => $json['locations']['end']['lon']);

			$this->stop_names = $json['stopnames'];
		}
		else
		{
			$this->source_label = getLabel($start);
			$this->dest_label = getLabel($end);
			$this->start_ll = getLocation($start);
			$this->end_ll = getLocation($end);

			$this->source_stops = nearbyStops($start);
			$this->dest_stops = nearbyStops($end);
			$routes = crossRoutes($this->source_stops, $this->dest_stops);
			$this->routes = array();

			$this->source_stops = array();
			$this->dest_stops = array();

			$stop_names = array();

			foreach($routes['routes'] as $uri => $route)
			{
				$stops = getAllStopsOnRoute(preg_replace("|^(.*)/([^/]*)$|", "$2", $uri));
				$stops_array = array();
				$i = 0;
				foreach($stops as $stop)
				{
					$stop['id'] = preg_replace("|^(.*)/([^/]*)$|", "$2", $stop['uri']);
					$stop_names[$stop['id']] = $stop['name'];
					$stop['location'] = getLocation($stop['uri']);
					if(!(array_key_exists("lat", $stop['location'])))
					{
						var_dump($stop['location']); exit();
					}
					$lat = $stop['location']['lat'];
					$lon = $stop['location']['lon'];
					if((array_key_exists("lat", $this->start_ll)) & (array_key_exists("lat", $this->start_ll)))
					{
						$stop['start_dist'] = distance($lat, $lon, $this->start_ll['lat'], $this->start_ll['lon']);
					} else {
						if(in_array($stop['id'], $this->source_stops))
						{
							$stop['start_dist'] = 0.0;
						} else {
							$stop['start_dist'] = 99.99;
						}
					}
					if((array_key_exists("lat", $this->start_ll)) & (array_key_exists("lat", $this->start_ll)))
					{
						$stop['end_dist'] = distance($lat, $lon, $this->end_ll['lat'], $this->end_ll['lon']);
					} else {
						if(in_array($stop['id'], $this->dest_stops))
						{
							$stop['end_dist'] = 0.0;
						} else {
							$stop['end_dist'] = 99.99;
						}
					}
					$stop['sequence'] = $i;
					$stops_array[] = $stop;
					$i++;
				}

				usort($stops_array, "sortStart");
				$start_stop = $stops_array[0]['sequence'];
				usort($stops_array, "sortEnd");
				$end_stop = $stops_array[0]['sequence'];
				usort($stops_array, "sortSequence");
				$route['stops'] = array();
				$i = $start_stop;
				for($i = $start_stop; $i <= $end_stop; $i++)
				{
					$stop_id = $stops_array[$i]['id'];
					$this->stop_names[$stop_id] = $stop_names[$stop_id];
					$route['stops'][] = $stop_id;
				}
				$stop_geton = $stops_array[$start_stop]['id'];
				$stop_getoff = $stops_array[$end_stop]['id'];
				if(!(in_array($stop_geton, $this->source_stops)))
				{
					$this->source_stops[] = $stop_geton;
				}
				if(!(in_array($stop_getoff, $this->dest_stops)))
				{
					$this->dest_stops[] = $stop_getoff;
				}

				$this->routes[$uri] = $route;
			}

			$fp = fopen($cachefile, "w");
			fwrite($fp, $this->toJson());
			fclose($fp);
		}
	}
}
