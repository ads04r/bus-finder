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
		$data = get_stop_data($this->stop_code, $max_rows);
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
		if(file_exists($this->cache . ".rdf"))
		{
			return(file_get_contents($this->cache . ".rdf"));
		}

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
