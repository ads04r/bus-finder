<?php

require_once( "/var/wwwsites/phplib/bus-stops-lib.php" ); // Not included in the public source code - I didn't write it and I don't own the copyright. Sorry!

$stopcode = $_GET['id'];
$format = $_GET['format'];

sanitise( $stopcode );
sanitise( $format );

$max_rows = 5;
if(array_key_exists("max", $_GET))
{  
        $max_rows = (int) $_GET['max'];
        if($max_rows < 1)
        {
                $max_rows = 1;
        }
}
 
if ( !isset($stopcode ) )
{
        $data =array( "error"=>array( "code"=>"401", "message"=>"Bad parameters used to retrieve real time info" ));
}
else
{
        global $bus_stops_error;
        global $bus_stops_error_msg;

        $data = get_stop_data( $stopcode, $max_rows );
        if( $data == null )
        {
                $data= array( "error"=>array( "code"=>$bus_stops_error, "message"=>$bus_stops_error_msg ) );
        }
}

if( $format == "json" )
{
        if( isset($_GET['callback']) && !empty($_GET['callback']) ) { echo $_GET['callback'] . "("; }
        echo json_encode($data);
        if( isset($_GET['callback']) && !empty($_GET['callback']) ) { echo ");"; }
        exit;
}

exit();
