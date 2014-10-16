<?php

class BusOperator
{
	private $noc;
	private $rdf;
	private $uri;
	private $rdesc;
	private $cache;

	public function id()
	{
		return $this->noc;
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
		function sort_op_routes($a, $b)
		{
			$d = strcmp($a['label'], $b['label']);
			if($d != 0) { return($d); }
			if($a['stops'] < $b['stops']) { return 1; }
			if($a['stops'] > $b['stops']) { return -1; }
			return 0;
		}

		$ret = array();
		$routes = array();
		foreach($this->rdf->all("-http://vocab.org/transit/terms/agency") as $route)
		{
			$id = "" . $route->get("skos:notation");
			$desc = "" . $route->label();
			$key = "r_" . $id;
			if(array_key_exists($key, $routes))
			{
				$item = $routes[$key];
			} else {
				$item = array();
				$item['id'] = $id;
				$item['routes'] = array();
			}
			$routeitem = array();
			$routeitem['uri'] = "" . $route;
			$routeitem['label'] = $desc;
			$routeitem['stops'] = count($route->all("http://vocab.org/transit/terms/routeStop"));
			$item['routes'][] = $routeitem;
			$routes[$key] = $item;
		}
		foreach($routes as $key=>$item)
		{
			usort($item['routes'], "sort_op_routes");
			$ret[] = $item;
		}
		return($ret);
	}

	public function toRdf()
	{
		$g = new Graphite();
		$g->addTurtle("", $this->generateTtl());
		return($g->serialize("RDFXML"));
	}

	public function toTtl()
	{
		$g = new Graphite();
		$g->addTurtle("", $this->generateTtl());
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

	function __construct($id, $endpoint)
	{
		$this->noc = $id;
		$this->uri = "http://id.southampton.ac.uk/bus-operator/" . $id;

		$this->cache = "./cache/operators/" . $id;
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
			$this->rdesc->addRoute( "naptan:route/*" );
			$this->rdesc->addRoute( "-naptan:agency/*" );
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
		}
	}
}
