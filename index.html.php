<?php

include "./include/datasearch.php";
global $PAGE;

$view = "";
@$view = $_GET['view'];

$points = array();
$f = file("./config/startpoints.csv");
foreach($f as $l)
{
	$a = explode(",", trim($l));
	$item = array();
	$item['title'] = $a[0];
	$item['lat'] = $a[1];
	$item['lon'] = $a[2];
	$points[] = $item;
}


$mobileuri = "http://data.southampton.ac.uk/bus-finder/?view=mob";

?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
		<title>Southampton Buses</title>
		<meta http-equiv="Content-Language" content="English" />
		<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
		<link rel="stylesheet" type="text/css" href="./styles/buses.css" />
		<link rel="stylesheet" type="text/css" href="./styles/jqueryui.css" />
		<link rel="shortcut icon" href="./favicon.png" />
		<script type="text/javascript" src="./javascript/jquery-1.8.2.js"></script>
		<script type="text/javascript" src="./javascript/buslistener.js"></script>
		<script type="text/javascript" src="./javascript/datadumper.js"></script>
		<script type="text/javascript" src="./javascript/bus-finder-main.js"></script>
	</head>
	<body>
		<div id="search">
			<div class="header">Bus Finder</div>
			<div class="content"><p>Type a destination.</p></div>
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

?>

		<h1>Southampton Bus Finder</h1>

		<div style='float:right; width:300px; text-align:center; border-left: solid 1px #CFCFCF; margin-left: 16px;'>
			<div>Use this handy QRCode to use this tool on your mobile device...</div>
			<a href="<? print($mobileuri); ?>">
				<img src="<? print("http://splashurl.net/qr.png?s=6&q=" . urlencode($mobileuri)); ?>" />
			</a>
		</div>

		<p style="width: 100%; display: block;">
			This is a tool that really shows off the power of linked open data. From here you can search for anything within the boundaries of
			Southampton and find a direct bus, if one exists. The service uses bus route data collected from the council, information on notable
			things in Southampton which is pulled from <a href="http://dbpedia.org/">DBPedia</a>, and information on food-serving outlets in
			Southampton which we get from the <a href="http://ratings.food.gov.uk/\">Food Standards Agency</a>.
		</p>

		<form action="./search.html" method="POST">
		<table>
			<tr>
				<td>Your current location:</td>
				<td>
					<select size="1" name="locsel" id="locsel">

<?

$sp = file("./config/startpoints.csv");
$i = 1;
foreach($sp as $l)
{
	$a = explode(",", trim($l));
	print("\t\t\t\t\t\t<option value=\"" . $i . "\">" . $a[0] . "</option>\n");
	$i++;
}

?>
					</select>
				</td>
			</tr>
			<tr>
				<td>Your destination:</td>
				<td><input type="text" id="searchfield" name="searchfield"></td>
			</tr>
			<tr>
				<td colspan="2"><input type="hidden" id="searchuri" name="searchuri" value=""><input type="submit" value="Find a bus"></td>
			</tr>
		</table>
		</form>

	<?

