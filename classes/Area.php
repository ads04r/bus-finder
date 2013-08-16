<?php

class Area
{
        private $id;
	private $info;
        private $rdf;
        private $uri;
        private $rdesc;

	function id()
	{
		return($this->id);
	}

	function uri()
	{
		return($this->uri);
	}

	function label()
	{
		if(array_key_exists("title", $this->info))
		{
			return($this->info['title']);
		}
		return("");
	}

	function toJson()
	{
		return(json_encode($this->info));
	}

	function toRdf()
	{
                $rdfxml = $this->rdf->serialize("RDFXML");

                return($rdfxml);
	}

	function toTtl()
	{
                $rdf = $this->rdf->serialize("Turtle");

                return($rdf);
	}

	function __construct($new_id)
	{
		$this->id = $new_id;
		$json = json_decode(file_get_contents("./config/startpoints.json"), true);
		$this->info = array();
		$this->uri = "";
		if(@array_key_exists($new_id, $json))
		{
			$this->info = $json[$new_id];
			@$this->uri = $this->info['uri'];
		}

		if(strlen($this->uri) > 0)
		{
	                $graph = new Graphite();
	                $graph->ns("soton", "http://id.southampton.ac.uk/ns/");
			$graph->load($this->uri);

			$graph->addCompressedTriple("", "foaf:primaryTopic", $this->uri);

			foreach($this->info['stops'] as $stop)
			{
				$stopuri = "http://id.southampton.ac.uk/bus-stop/" . $stop;
				//$graph->load($stopuri);
				$graph->addCompressedTriple($this->uri, "foaf:based_near", $stopuri);
			}

	                $this->rdf = $graph;
		}

	}
}
