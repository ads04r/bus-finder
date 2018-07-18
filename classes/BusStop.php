<?php

class BusStop
{
	private $stop_code;
	private $rdf;
	private $uri;
	private $rdesc;
	private $cache;

	public function stopCode()
	{
		return $this->stop_code;
	}

	public function uri()
	{
		return $this->uri;
	}

	public function label()
	{
		return "" . $this->rdf->label();
	}

	public function routes()
	{
		function routeSort($a, $b)
		{
			$cmp = strnatcmp($a['code'], $b['code']);
			if($cmp != 0)
			{
				return($cmp);
			}
			if($a['stop_count'] < $b['stop_count']) {
				return 1;
			}
			if($a['stop_count'] > $b['stop_count']) {
				return -1;
			}
			return 0;
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
			if(!(array_key_exists("operator", $item)))
			{
				$item['operator'] = "";
			}
			$ret[] = $item;
		}
		usort($ret, "routeSort");
		return($ret);
	}

	public function toJson($max_rows = 5)
	{
		if($max_rows > 5)
		{
			$data = get_stop_data_jmw($this->stop_code, $max_rows);
		}
		else
		{
			$data = get_stop_data($this->stop_code, $max_rows);
		}
		if( $data == null )
		{
			$data = array();
		}
		return(json_encode($data));
	}

	public function toRaw($max_rows = 5)
	{
		$data = get_stop_data( $this->stop_code, $max_rows );
		if( $data == null )
		{
			return "";
		}

		$html = "";
		$html .= "<div id='BUSTIME' style='padding-right:10px;text-align:right'>&nbsp;</div>\n";
		$html .= "<script>\n";
		$html .= "busTimeFrom = (new Date()).getTime() - 1000*" . $data['age'] . ";\n";
		$html .= "bstimer();\n";
		$html .= "</script>\n";
		$html .= "<table cellspacing='0' class='bus_display'>\n";
		foreach($data['stops'] as $stop)
		{
			$html .= "<tr>";
			$html .= "<td class='bus_display_cell_name'>" . $stop['name'] . "</td>";
			$html .= "<td class='bus_display_cell_dest'>" . $stop['dest'] . "</td>";
			$html .= "<td class='bus_display_cell_time'>" . $stop['time'] . "</td>";
			$html .= "</tr>";
		}
		$html .= "</table>";

		return($html);
	}

	public function toKml()
	{
		if(file_exists($this->cache . ".kml"))
		{
			return(file_get_contents($this->cache . ".kml"));
		}

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
		$g = new Graphite();
		$g->addTurtle("", $this->generateTtl());
		$g->addTurtle("", $this->generateTimesTtl());
		return($g->serialize("RDFXML"));
	}

	public function toTtl()
	{
		$g = new Graphite();
		$g->addTurtle("", $this->generateTtl());
		$g->addTurtle("", $this->generateTimesTtl());
		return($g->serialize("Turtle"));
	}

	private function generateTimesTtl()
	{
		$data = get_stop_data($this->stop_code, 5);
		$stops = array();
		if(array_key_exists("stops", $data))
		{
			$stops = $data['stops'];
		}
		$i = 1;
		$g = new Graphite();
		foreach($stops as $stop)
		{
			$dt = strtotime(date("Y-m-d") . " " . $stop['time'] . ":00");
			$now = time();
			if($dt < $now)
			{
				$dt = $dt + 86400; // Not all future times are today!
			}

			$g->addTriple("_:b" . $i, "http://www.w3.org/1999/02/22-rdf-syntax-ns#type", "http://id.southampton.ac.uk/ns/TimetabledBusArrivalEvent");
			$g->addTriple("_:b" . $i, "http://purl.org/dc/terms/source", "http://bus.southampton.ac.uk/bus-stop/" . $this->stop_code . ".ttl");
			$g->addTriple("_:b" . $i, "http://purl.org/NET/c4dm/event.owl#place", "http://id.southampton.ac.uk/bus-stop/" . $this->stop_code);
			$g->addTriple("_:b" . $i, "http://purl.org/NET/c4dm/event.owl#time", "_:b" . ($i + 1));
			$g->addTriple("_:b" . $i, "http://id.southampton.ac.uk/ns/vehicleName", $stop['name'], "http://id.southampton.ac.uk/ns/bus-route-id-scheme");
			$g->addTriple("_:b" . $i, "http://www.w3.org/2004/02/skos/core#notation", $stop['name'], "http://id.southampton.ac.uk/ns/bus-route-id-scheme");
			$g->addTriple("_:b" . $i, "http://www.w3.org/2000/01/rdf-schema#label", $stop['name'] . ": " . $stop['dest'] . " - " . $stop['time'], "literal");
			$g->addTriple("_:b" . $i, "http://id.southampton.ac.uk/ns/vehicleDestination", "_:b" . ($i + 2));

			$g->addTriple("_:b" . ($i + 1), "http://www.w3.org/1999/02/22-rdf-syntax-ns#type", "http://purl.org/NET/c4dm/timeline.owl#Instant");
			$g->addTriple("_:b" . ($i + 1), "http://purl.org/NET/c4dm/timeline.owl#at", date("c", $dt), "http://www.w3.org/2001/XMLSchema#dateTime");

			$g->addTriple("_:b" . ($i + 2), "http://www.w3.org/2000/01/rdf-schema#label", $this->label(), "literal");

			$i = $i + 3;
		}
		return($g->serialize("Turtle"));
	}

	private function generateTtl()
	{
		if(file_exists($this->cache . ".ttl"))
		{
			return(file_get_contents($this->cache . ".ttl"));
		}

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
		$ll = $this->latLon();
		$lat = $ll['lat'];
		$lon = $ll['lon'];

		return("http://bus.southampton.ac.uk/graphics/staticmaplite/staticmap.php?center=" . $lat . "," . $lon . "&zoom=17&size=720x200&markers=" . $lat . "," . $lon . ",bus");
	}

	public function latLon()
	{
		$lat = (float) ("" . $this->rdf->get("geo:lat"));
		$lon = (float) ("" . $this->rdf->get("geo:long"));
		$ll = array('lat'=>$lat, 'lon'=>$lon);

		return($ll);
	}

	function __construct($new_stop_code, $endpoint)
	{
		$this->stop_code = $new_stop_code;
		$this->uri = "http://id.southampton.ac.uk/bus-stop/" . $new_stop_code;

		$this->cache = "./cache/stops/" . $new_stop_code;
		if(file_exists($this->cache . ".ttl"))
		{
			$graph = new Graphite();
			$graph->load($this->cache . ".ttl");

			$this->rdf = $graph->resource($this->uri);
		}

		else
		{
			$graph = new Graphite();
			$graph->ns("naptan", "http://vocab.org/transit/terms/");
			$graph->ns("soton", "http://id.southampton.ac.uk/ns/");

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

			$data = $this->toTtl();
			$fp = fopen($this->cache . ".ttl", 'w');
			fwrite($fp, $data);
			fclose($fp);

			$data = $this->toRdf();
			$fp = fopen($this->cache . ".rdf", 'w');
			fwrite($fp, $data);
			fclose($fp);

			$data = $this->toKml();
			$fp = fopen($this->cache . ".kml", 'w');
			fwrite($fp, $data);
			fclose($fp);
		}
	}
}
