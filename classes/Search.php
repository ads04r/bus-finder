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

	public function getStops()
	{
		$stops = array();
		$stops['start'] = $this->source_stops;
		$stops['end'] = $this->dest_stops;

		return($stops);
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

		return(json_encode($output));
	}

	function __construct($start, $end)
	{
		$this->source_uri = $start;
		$this->dest_uri = $end;
		$this->hash = md5($start . "\n" . $end);

		$cachefile = "./cache/" . $this->hash . ".json";
		if(file_exists($cachefile))
		{
			$json = json_decode(file_get_contents($cachefile), true);

			$this->source_label = $json['locations']['start']['label'];
			$this->dest_label = $json['locations']['end']['label'];
			$this->source_stops = $json['stops']['start'];
			$this->dest_stops = $json['stops']['end'];
			$this->routes = $json['routes'];
		}
		else
		{
			$this->source_label = getLabel($start);
			$this->dest_label = getLabel($end);

			$this->source_stops = nearbyStops($start);
			$this->dest_stops = nearbyStops($end);
			$routes = crossRoutes($this->source_stops, $this->dest_stops);
			$this->routes = $routes['routes'];

			$fp = fopen($cachefile, "w");
			fwrite($fp, $this->toJson());
			fclose($fp);
		}
	}
}
