<?php

class BusStop
{
	private $stop_code;
	private $rdf;
	private $uri;
	private $rdesc;

	public function stopCode()
	{
		return $this->stop_code;
	}

	public function label()
	{
		return $this->rdf->label();
	}

	public function routes()
	{
		function routeSort($a, $b)
		{
			return(strnatcmp($a['code'], $b['code']));
		}

		$data = get_stop_data( $this->stop_code, 1 );
		$ret = array();
		if( $data == null )
		{
			return(array());
		}
		@$routes = $data['routes'];
		if(!(is_array($routes)))
		{
			$routes = array();
		}
		foreach($routes as $uri => $info)
		{
			$item = $info;
			$item['uri'] = $uri;
			$item['url'] = "/bus-route/" . preg_replace("|(.*)/([^/]*)|", "$2", $uri) . ".html";
			$ret[] = $item;
		}
		usort($ret, "routeSort");
		return($ret);
	}

	public function toJson($max_rows = 5)
	{
		$data = get_stop_data( $this->stop_code, $max_rows );
		if( $data == null )
		{
			$data= array( "error"=>array( "code"=>$bus_stops_error, "message"=>$bus_stops_error_msg ) );
		}
		return(json_encode($data));
	}

	public function toKml()
	{
		ob_start();
		$err = $this->rdesc->handleFormat("kml");
		$kml = ob_get_contents();
		ob_end_clean();

		if($err)
		{
			return($kml);
		}
		return("");
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

		return("http://bus.southampton.ac.uk/graphics/staticmaplite/staticmap.php?center=" . $lat . "," . $lon . "&zoom=17&size=720x200&markers=" . $lat . "," . $lon . ",ol-marker-gold");
	}

	function __construct($new_stop_code, $endpoint)
	{

		$graph = new Graphite();
		$graph->ns("naptan", "http://vocab.org/transit/terms/");
		$graph->ns("soton", "http://id.southampton.ac.uk/ns/");
		$this->stop_code = $new_stop_code;
		$this->uri = "http://id.southampton.ac.uk/bus-stop/" . $new_stop_code;

		$resource = $graph->resource($this->uri);
		$this->rdesc = $resource->prepareDescription();
		$this->rdesc->addRoute( "*" );
		$this->rdesc->addRoute( "*/rdf:type" );
		$this->rdesc->addRoute( "*/rdfs:label" );
		$this->rdesc->addRoute( "-naptan:stop/*" );
		$this->rdesc->addRoute( "-naptan:stop/naptan:route/*" );
		$this->rdesc->addRoute( "-naptan:stop/naptan:route/soton:busRouteOperator/*" );
		$n = $this->rdesc->loadSPARQL($endpoint);

		$this->rdf = $this->rdesc->toGraph()->resource($this->uri);
	}
}
