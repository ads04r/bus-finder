<?php

class BusRoute
{
	private $route_code;
	private $rdf;
	private $uri;
	private $rdesc;

	public function routeCode()
	{
		return $this->route_code;
	}

	public function label()
	{
		$title = "" . $this->rdf->get("skos:notation");
		if(strlen($title) > 0)
		{
			$title = $title . " - " . $this->rdf->get("http://id.southampton.ac.uk/ns/busRouteOperator")->label();
		}
		if(strlen($title) == 0)
		{
			$title = $this->rdf->label();
		}
		return $title;
	}

	public function stops()
	{
		if(!(function_exists("stop_sort")))
		{
			function stop_sort($a, $b)
			{
				if($a['seq'] < $b['seq'])
				{
					return -1;
				}
				if($a['seq'] > $b['seq'])
				{
					return 1;
				}
				return 0;
			}
		}

		$stops = array();

		foreach($this->rdf->all("http://vocab.org/transit/terms/routeStop") as $rs)
		{
			$stopobj = $rs->get("http://vocab.org/transit/terms/stop");
			$stop = array();

			$uri = "" . $stopobj;
			$stop['id'] = preg_replace("|(.*)/([^/]*)|", "$2", $uri);
			$stop['uri'] = $uri;
			$stop['name'] = "" . $stopobj->label();
			$stop['seq'] = (int) ("" . $rs->get("http://vocab.org/transit/terms/sequence"));
			$stop['lat'] = (float) ("" . $stopobj->get("geo:lat"));
			$stop['lon'] = (float) ("" . $stopobj->get("geo:long"));

			$stops[] = $stop;
		}

		usort($stops, "stop_sort");
		return($stops);
	}

	public function toJson()
	{
		$data = array();

		$data['id'] = (int) $this->route_code;
		$data['uri'] = $this->uri;
		$data['number'] = "" . $this->rdf->get("skos:notation");
		$data['operator'] = "" . $this->rdf->get("http://id.southampton.ac.uk/ns/busRouteOperator")->label();
		$data['stops'] = $this->stops();
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
		$stops = $this->stops(); $i = 0; $p = 0;
		$maxlat = -999.0;
		$maxlon = -999.0;
		$minlat = 999.0;
		$minlon = 999.0;
		foreach($stops as $stop)
		{
			if($stop['lat'] > $maxlat)
			{
				$maxlat = $stop['lat'];
			}
			if($stop['lon'] > $maxlon)
			{
				$maxlon = $stop['lon'];
			}
			if($stop['lat'] < $minlat)
			{
				$minlat = $stop['lat'];
			}
			if($stop['lon'] < $minlon)
			{
				$minlon = $stop['lon'];
			}
		}
		$alat = (($maxlat - $minlat) / 2) + $minlat;
		$alon = (($maxlon - $minlon) / 2) + $minlon;
		$maxdist = 0.0;
		foreach($stops as $stop)
		{
			$dist = distance($alat, $alon, $stop['lat'], $stop['lon']);
			if($dist > $maxdist)
			{
				$maxdist = $dist;
			}
		}
		$zoom = 14 - ((int) $maxdist);
		if($zoom < 11)
		{
			$zoom = 11;
		}
		$url = "http://bus.southampton.ac.uk/graphics/staticmap/?lat=" . $alat . "&lon=" . $alon . "&z=" . $zoom . "&w=720&h=480&mode=Draw&";
		foreach($stops as $stop)
		{
			$url .= "d" . $p . "p" . $i . "lat=" . $stop['lat'] . "&d" . $p . "p" . $i . "lon=" . $stop['lon'] . "&";
			$i++;
			if($i > 14)
			{
				$i = 0;
				$p++;
				$url .= "d" . $p . "p" . $i . "lat=" . $stop['lat'] . "&d" . $p . "p" . $i . "lon=" . $stop['lon'] . "&";
				$i++;
			}
		}
		$url .= "show=1#" . $maxdist; // 2.224 = 12, 1.931 = 13

		//return("http://bus.southampton.ac.uk/graphics/staticmaplite/staticmap.php?center=" . $lat . "," . $lon . "&zoom=17&size=720x480&markers=" . $lat . "," . $lon . ",ol-marker-gold");
		//return("http://bus.southampton.ac.uk/graphics/staticmap/?lat=50.935164927467&lon=-1.3974952697754&z=15&w=720&h=480&mode=Draw&d0_colour=00F&d0p0lat=50.933109445022&d0p0lon=-1.3955640792847&d0p1lat=50.933812646608&d0p1lon=-1.3959074020386&d0p2lat=50.934272426512&d0p2lon=-1.3962936401367&d0p3lat=50.934813338226&d0p3lon=-1.3964223861694&d0p4lat=50.935354243649&d0p4lon=-1.3962078094482&d0p5lat=50.935705828801&d0p5lon=-1.3960790634155&d0p6lat=50.936544213434&d0p6lon=-1.3962507247925&d0p7lat=50.937220319065&d0p7lon=-1.3964223861694&dp_num=8&show=1");
		return($url);
	}

	function __construct($new_route_code, $endpoint)
	{

		$graph = new Graphite();
		$graph->ns("naptan", "http://vocab.org/transit/terms/");
		$graph->ns("soton", "http://id.southampton.ac.uk/ns/");
		$this->route_code = $new_route_code;
		$this->uri = "http://id.southampton.ac.uk/bus-route/" . $new_route_code;

		$resource = $graph->resource($this->uri);
		$this->rdesc = $resource->prepareDescription();
		$this->rdesc->addRoute( "*" );
		$this->rdesc->addRoute( "*/rdf:type" );
		$this->rdesc->addRoute( "*/rdfs:label" );
		$this->rdesc->addRoute( "naptan:routeStop/*" );
		$this->rdesc->addRoute( "naptan:routeStop/naptan:stop/*" );
		$n = $this->rdesc->loadSPARQL($endpoint);

		$this->rdf = $this->rdesc->toGraph()->resource($this->uri);
	}
}
