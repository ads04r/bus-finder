<?php

include "./include/datasearch.php";
global $PAGE;

$view = "";
@$view = $_GET['view'];

$points = json_decode(file_get_contents("./config/startpoints.json"), true);

?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
		<title>Southampton Buses</title>
		<meta http-equiv="Content-Language" content="English" />
		<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
		<link rel="stylesheet" type="text/css" href="./styles/buses.css" />
		<link rel="stylesheet" type="text/css" href="./styles/jquery-ui-1.9.2.custom.min.css" />
		<link rel="shortcut icon" href="./favicon.png" />
		<script type="text/javascript" src="./javascript/jquery-1.8.2.js"></script>
		<script type="text/javascript" src="./javascript/jquery-ui-1.9.2.custom.min.js"></script>
		<script type="text/javascript" src="./javascript/buslistener.js"></script>
		<script type="text/javascript" src="./javascript/datadumper.js"></script>
		<script type="text/javascript" src="./javascript/bus-finder-main.js"></script>
	</head>
	<body>
		<div id="search">
			<div class="header">Bus Finder</div>
			<div class="content">
				<p>Type a destination.</p>
				<input type="text" name="searchfield" id="searchfield">
				<input type="hidden" name="searchuri" id="searchuri" value="">
				<input type="hidden" name="sourceuri" id="sourceuri" value="<?

foreach($points as $point)
{
	if(count($point['stops']) > 0)
	{
		print($point['uri']);
		break;
	}
}

?>">
			</div>
		</div>
		<div id="campusselect">
			<div class="header">Search by site</div>
			<div class="content"><ul id="site">

<?

foreach($points as $point)
{
	if(count($point['stops']) > 0)
	{
		print("				<li><a href=\"" . $point['uri'] . "\">" . $point['title'] . "</a></li>\n");
	}
}

?>

			</ul></div>
		</div>
		<div id="footer">
			<ul>
				<li><a href="http://www.southampton.ac.uk/">University homepage</a></li>
				<li><a href="http://www.southampton.ac.uk/visitus/">How to get here</a></li>
				<li><a href="mailto:opendata@southampton.ac.uk">Contact us</a></li>
				<li><a href="http://data.southampton.ac.uk/dataset/bus-info.html">Get the Data</a></li>
			</ul>
		</div>
		<div id="main">
			<div class="header"><h1 id="listtitle">Southampton Bus Information</h1></div>
			<div class="content">
				<h2>Loading bus data...</h2>
			</div>
		</div>
	</body>
</html><?

exit();

