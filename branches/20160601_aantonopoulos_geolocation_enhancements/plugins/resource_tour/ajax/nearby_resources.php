<?php
include "../../../include/db.php";
include_once "../../../include/general.php";
include "../../../include/authenticate.php";
include "../../../include/resource_functions.php";

global $baseurl;

#Approximation from http://wiki.openstreetmap.org/wiki/Mercator
function lon2x($lon) {
	$val = deg2rad(floatval($lon))*6378137.0;
	return $val;
	}
function lat2y($lat) {
	$val = log(tan(M_PI_4 + deg2rad(floatval($lat)) / 2.0)) * 6378137.0;
	return $val;
	}
	
//ALL VALUES NEED TO BE ESCAPED

//echo $_POST['jsonData'];
$PostData = json_decode($_POST['jsonData']);

$radius = $PostData->{'Radius'};
//echo($radius);
$lon =  $PostData->{'coord'}->{'x'};
$lat =  $PostData->{'coord'}->{'y'};
$merc_lon = lon2x(floatval($lon));
$merc_lat = lat2y(floatval($lat));

$Bound_North = $PostData->{'Bound_North'}->{'y'};
$Bound_South = $PostData->{'Bound_South'}->{'y'};
$Bound_West  = $PostData->{'Bound_West'}->{'x'};
$Bound_East  = $PostData->{'Bound_East'}->{'x'};

$filter = sql_query("select ref as value from resource where ( geo_lat>'$Bound_South' and geo_lat<'$Bound_North' and geo_long>'$Bound_West' and geo_long<'$Bound_East');","");

#FIND THE LINEAR DISTANCE BETWEEN POINTS AND PASS THEM UNDERNEATH TO CREATE A DISC
$all_resources = $filter;

$i=0;
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
		$x = lon2x($resource['geo_long']);
		//echo $x;
		$y = lat2y($resource['geo_lat']);
		
		$lin_dist = sqrt( pow($merc_lat - $y,2) + pow($merc_lon - $x,2) ) ;
		
		if ($lin_dist < $radius){
			
			$markers[$i] = array( 'lon'=> $resource['geo_long'], 'lat'=>  $resource['geo_lat'] , 'res'=>$resource['ref'] ,'thumbwidth'=> $forthumb['thumb_width'] ,'thumbheight'=> $forthumb['thumb_height'] ,'url'=> $parts[0] );
			
			$i++;
			}
		}
	}
	

if (isset($markers)){
	echo json_encode($markers);
}

else{
	echo json_encode('cows go moo when they poo');
}
