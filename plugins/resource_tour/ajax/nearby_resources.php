<?php
include "../../../include/db.php";
include_once "../../../include/general.php";
include "../../../include/authenticate.php";
include "../../../include/resource_functions.php";

global $baseurl;

#Approximation from http://wiki.openstreetmap.org/wiki/Mercator
function lon2x($lon) { return deg2rad($lon) * 6378137.0; }
function lat2y($lat) { return log(tan(M_PI_4 + deg2rad($lat) / 2.0)) * 6378137.0; }

//echo $_POST['jsonData'];
$myPos = json_decode($_POST['jsonData']);
#print_r($myPos);

$lon = $myPos->{'coord'}->{'x'};
$lat = $myPos->{'coord'}->{'y'};

$Bound_North = $myPos->{'Bound_North'}->{'y'};
$Bound_South = $myPos->{'Bound_South'}->{'y'};
$Bound_West  = $myPos->{'Bound_West'}->{'x'};
$Bound_East  = $myPos->{'Bound_East'}->{'x'};

#echo $Bound_East;
//$lon = lon2x($myPos->{'coord'}->{'x'});
//$lat = lat2y($myPos->{'coord'}->{'y'});
#echo $lat . '<br>';
#echo $lon . '<br>';

/*$geolong = lon2x($lon);
$geolat  = lat2y($lat);
echo $geolat . '<br>';
echo $geolong . '<br>';*/


#$result = sql_value("select ref as value from resource where sqrt(power('$lat' - " . lat2y('geo_lat') . ",2) + power('$lon' - " . lon2x('geo_long') .",2)) < 10000;","");

$filter = sql_query("select ref as value from resource where ( geo_lat>'$Bound_South' and geo_lat<'$Bound_North' and geo_long>'$Bound_West' and geo_long<'$Bound_East');","");


#FIND THE LINEAR DISTANCE BETWEEN POINTS AND PASS THEM UNDERNEATH TO CREATE A DISC
$all_resources = $filter;
//print_r( $all_resources);
//Start looping through the data fetched earlier

foreach ($all_resources as $value) 
	{
	$val = $value['value'];
    $resource = get_resource_data($val,$cache=false);
    //print_r($resource);
    //hide the resource if it is confidential
	if ( get_resource_access($resource['ref'])==2 ) {continue;}
    
    //If the resource is not confidential keep going
    else
		{
		$forthumb = get_resource_data($resource['ref']);
		$url = get_resource_path($resource['ref'],false,"thm",$generate=true,$extension="jpg",$scramble=-1,$page=1,$watermarked=false,$file_modified="",$alternative=-1,$includemodified=true);
		$new = str_replace($baseurl,"", $url);
		$parts =  explode('?',$new);
		//$paths[] = $parts[0];
		$markers[] =  [ $resource['geo_long'] . "," .  $resource['geo_lat'] . "," . $resource['ref'] . "," . $forthumb['thumb_width'] . "," . $forthumb['thumb_height'] . "," . $parts[0] ];
		
		}
	}

echo json_encode($markers);

#echo json_encode($result);
