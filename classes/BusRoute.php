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
		return($this->rdesc->serialize("RDFXML"));
	}

	public function toTtl()
	{
		return($this->rdesc->serialize("Turtle"));
	}

	public function polyline()
	{
		$url = "http://api.bus.southampton.ac.uk/route/" . $this->route_code . "/points";
		$ret = json_decode(file_get_contents($url), true);
		if(!(is_array($ret))) { $ret = array(); }

		if(count($ret) > 0) { return(json_encode($ret)); }

		$stops = $this->stops();
		foreach($stops as $stop)
		{
			$item = array();
			$item[] = $stop['lat'];
			$item[] = $stop['lon'];
			$ret[] = $item;
		}

		return(json_encode($ret));
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

		$g = new Graphite();
		$g->ns("naptan", "http://vocab.org/transit/terms/");
		$g->ns("soton", "http://id.southampton.ac.uk/ns/");
		$g->ns("geo", "http://www.w3.org/2003/01/geo/wgs84_pos#");
		$g->ns("oo", "http://purl.org/openorg/");
		$this->route_code = $new_route_code;
		$this->uri = "http://id.southampton.ac.uk/bus-route/" . $new_route_code;

		$data = json_decode(file_get_contents("http://api.bus.dev.southampton.ac.uk/route/" . $new_route_code), true);
		$stops = json_decode(file_get_contents("http://api.bus.dev.southampton.ac.uk/route/" . $new_route_code . "/stops"), true);

		$noc_uri = "http://id.southampton.ac.uk/bus-operator/" . $data['operator']['noc'];

		$g->t($this->uri, "rdf:type", "soton:BusRoute");
		$g->t($this->uri, "rdf:type", "naptan:BusRoute");
		$g->t($this->uri, "rdf:type", "soton:BusRoute" . ucwords(strtolower($data['direction'])));
		$g->t($this->uri, "rdfs:label", $data['description'], "literal");
		$g->t($this->uri, "skos:notation", $data['number'], "soton:bus-route-id-scheme");
		$g->t($this->uri, "foaf:page", "http://bus.southampton.ac.uk/bus-route/" . $new_route_code . ".html");
		$g->t($this->uri, "soton:busRouteOperator", $noc_uri);
		$g->t($this->uri, "naptan:agency", $noc_uri);

		$g->t($noc_uri, "rdf:type", "soton:BusOperator");
		$g->t($noc_uri, "rdf:type", "naptan:Agency");
		$g->t($noc_uri, "rdfs:label", $data['operator']['name'], "literal");

		$i = 1;
		foreach($stops as $stop)
		{
			$stop_uri = "http://id.southampton.ac.uk/bus-stop/" . $stop['id'];
			$route_stop_uri = "http://id.southampton.ac.uk/bus-route/" . $new_route_code . "/" . $i . "-" . $stop['id'];
			$g->t($this->uri, "naptan:routeStop", $route_stop_uri);

			$g->t($route_stop_uri, "rdf:type", "soton:BusRouteStop");
			$g->t($route_stop_uri, "rdf:type", "naptan:RouteStop");
			$g->t($route_stop_uri, "soton:busRouteSequenceNumber", $i, "xsd:nonNegativeInteger");
			$g->t($route_stop_uri, "soton:busStoppingAt", $stop_uri);
			$g->t($route_stop_uri, "soton:inBusRoute", $this->uri);
			$g->t($route_stop_uri, "naptan:sequence", $i, "xsd:nonNegativeInteger");
			$g->t($route_stop_uri, "naptan:stop", $stop_uri);
			$g->t($route_stop_uri, "naptan:route", $this->uri);

			$g->t($stop_uri, "rdf:type", "http://transport.data.gov.uk/def/naptan/BusStop");
			$g->t($stop_uri, "rdf:type", "naptan:Stop");
			$g->t($stop_uri, "rdfs:label", $stop['commonname'], "literal");
			$g->t($stop_uri, "oo:mapIcon", "http://data.southampton.ac.uk/map-icons/Transportation/bus.png");
			$g->t($stop_uri, "geo:lat", $stop['latitude'], "xsd:float");
			$g->t($stop_uri, "geo:long", $stop['longitude'], "xsd:float");
			$g->t($stop_uri, "skos:notation", $stop['id'], "soton:bus-stop-id-scheme");
			$g->t($stop_uri, "foaf:page", "http://bus.southampton.ac.uk/bus-stop/" . $stop['id'] . ".html");
			$g->t($stop_uri, "soton:liveBusTimes", "http://bus.southampton.ac.uk/bus-stop/" . $stop['id'] . ".json");
			$g->t($stop_uri, "soton:mobilePage", "http://bus.southampton.ac.uk/bus-stop-mobile/" . $stop['id'] . ".html");

			$i++;
		}

		$resource = $g->resource($this->uri);

		$this->rdesc = $g;
		$this->rdf = $resource;
	}
}
