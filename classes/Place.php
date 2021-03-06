<?php

include_once("./include/functions.php");

class Place
{
	private $fhrs_code;
	private $rdf;
	private $uri;
	private $rdesc;
	private $stops;

	public function fhrsCode()
	{
		return $this->fhrs_code;
	}

	public function label()
	{
		return $this->rdf->label();
	}

	public function toJson()
	{
		$near_stops = $this->stops;
		foreach($near_stops as $stop)
		{
			$stops[] = preg_replace("|(.*)/([^/]*)|", "$2", $stop['uri']);
		}
		$info = array();
		$info['id'] = "" . $this->fhrs_code;
		$info['name'] = "" . $this->rdf->label();
		$info['stops'] = $stops;
		return(json_encode($info));
	}

	public function toRdf()
	{
		ob_start();
		$err = $this->rdesc->handleFormat("rdf");
		$rdfxml = ob_get_contents();
		ob_end_clean();

		if($err)
		{
			return($rdfxml);
		}
		return("");
	}

	public function toTtl()
	{
		ob_start();
		$err = $this->rdesc->handleFormat("ttl");
		$rdfttl = ob_get_contents();
		ob_end_clean();

		if($err)
		{
			return($rdfttl);
		}
		return("");
	}

        public function mapImage()
        {
		$lat = $this->rdf->get("geo:lat");
		$lon = $this->rdf->get("geo:long");

		return("http://bus.southampton.ac.uk/graphics/staticmaplite/staticmap.php?center=" . $lat . "," . $lon . "&zoom=16&size=720x200");
        }

	function __construct($new_fhrs_code, $endpoint)
	{

		$graph = new Graphite();
		$graph->ns("naptan", "http://vocab.org/transit/terms/");
		$graph->ns("soton", "http://id.southampton.ac.uk/ns/");
		$this->fhrs_code = $new_fhrs_code;
		$this->uri = "http://ratings.food.gov.uk/business/" . $new_fhrs_code . "#subject";
		$this->stops = array();

		$near_stops = nearestStops($this->uri);
		foreach($near_stops as $stop)
		{
			if($stop['dist'] > 403) // We don't care about stops over 403m away. For those interested, the average human walks at 3mph, which is 402.336 metres in 5 minutes.
			{
				continue;
			}
			$stopuri = $stop['uri'];
			$graph->addCompressedTriple($this->uri, "foaf:based_near", $stopuri);
			$graph->addCompressedTriple($stopuri, "rdfs:label", $stop['title']);
			$this->stops[] = $stop;
		}

		$resource = $graph->resource($this->uri);
		$this->rdesc = $resource->prepareDescription();
		$this->rdesc->addRoute( "*" );
		$this->rdesc->addRoute( "*/rdf:type" );
		$this->rdesc->addRoute( "*/rdfs:label" );
		$n = $this->rdesc->loadSPARQL($endpoint);

		$this->rdf = $this->rdesc->toGraph()->resource($this->uri);
	}
}
