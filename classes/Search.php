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

	public function toJson()
	{
		$output = array();

		$output['routes'] = $self->routes;
		$output['stops'] = array();
		$output['stops']['start'] = $self->source_stops;
		$output['stops']['end'] = $self->dest_stops;
		$output['locations'] = array();
		$output['locations']['start'] = array();
		$output['locations']['end'] = array();
		$output['locations']['start']['uri'] = $self->source_uri;
		$output['locations']['end']['uri'] = $self->dest_uri;
		$output['locations']['start']['label'] = $self->source_label;
		$output['locations']['end']['label'] = $self->dest_label;

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
			$json = json_decode(file_get_contents($cachefile));

			$this->source_label = $json['locations']['start']['label'];
			$this->end_label = $json['locations']['end']['label'];
			$this->source_stops = $json['stops']['start'];
			$this->dest_stops = $json['stops']['end'];
			$this->routes = $json['routes'];
		}
		else
		{
			$g = new Graphite();
			$g->load($start);
			$g->load($end);
			$this->source_label = "" . $g->resource($start)->label();
			$this->dest_label = "" . $g->resource($end)->label();

			$this->source_stops = nearbyStops($start);
			$this->dest_stops = nearbyStops($end);
			$this->routes = crossRoutes($this->source_stops, $this->dest_stops);

			$fp = fopen($cachefile, "w");
			fwrite($fp, $this->toJson());
			fclose($fp);
		}
	}
}
