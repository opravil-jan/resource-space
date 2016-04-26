<?php
include "../include/db.php";
include_once "../include/general.php";
include "../include/authenticate.php";
include "../include/resource_functions.php";
include "../include/collections_functions.php";
include "../include/header.php";

if ( $disable_geocoding || (!$disable_geocoding && !$geo_locate_collection) ){exit($lang["error-permissiondenied"]);}

global $baseurl;

$ref = getvalescaped("ref","",true);
$all_resources = get_collection_resources($ref);
$collection = get_collection($ref);
$collectionname = $collection['name'];
$markers = array();
$mean_lat = 0;
$mean_long = 0;
$check = false;
?>

<h1><?php echo $lang["geolocatecollection"] ?></h1>
<h3><?php echo $lang["collectionname"] . ": " . $collectionname  ?></h3>

<?php

//If the collection is empty stop here and provide a message
if ( count($all_resources) == 0 ) {  exit( $lang["geoemptycollection"]);  }

//Start looping through the data fetched earlier
foreach ($all_resources as $value) 
	{
    $resource = get_resource_data($value,$cache=true);
	//echo get_resource_access($resource['ref']);
    $forthumb = get_resource_data($resource['ref']);
    $url = get_resource_path($resource['ref'],false,"thm",$generate=true,$extension="jpg",$scramble=-1,$page=1,$watermarked=false,$file_modified="",$alternative=-1,$includemodified=true);
	$new = str_replace($baseurl,"", $url);
	$parts =  explode('?',$new);
	if ( $resource['geo_long'] == '' || $resource['geo_lat'] == '')
		{
		if (!$check)
			{
			echo $lang['location-missing'] ;
			//Set check to true so the text above and the table below 
			//are only rendered if geolocation data are missing
			$check = true;
			?>
			<table class="InfoTable">
			<tr>
			<td><?php echo $lang["resourceid"]?></td>
			<td><?php echo $lang["action-preview"]?></td>
			<td><?php echo $lang['location-title']?></td>
			</td>
			</tr>
			<?php
			}
			?>

		<tr>
		<td><?php echo $resource['ref']?></td>
		<td><a href=<?php echo $baseurl . "/pages/view.php?ref=" . $resource['ref'] ?> > <img  src=<?php echo '..' . $parts[0]?> </img></a></td>
		<?php if (get_edit_access($resource['ref'])){?><td><a href=<?php echo $baseurl . "/pages/geo_edit.php?ref=" . $resource['ref'] ?> > <?php echo $lang['location-add']?></a></td><?php } else { ?><td> <?php echo $lang['location-noneselected'];?> </td><?php } ?>
		</tr>
		
		
		<?php
		}
	else
		{
		//These arrays are going to be passed to Javascript below to plot
		$markers[] =  [ $resource['geo_long'] . "," .  $resource['geo_lat'] . "," . $resource['ref'] . "," . $forthumb['thumb_width'] . "," . $forthumb['thumb_height']];
		$paths[] = $parts[0];
		$mean_long=$mean_long+$resource['geo_long'];
		$mean_lat=$mean_lat+$resource['geo_lat']; 
		}
	}

$mean_lat=$mean_lat/count($markers);
$mean_long=$mean_long/count($markers);

?>
<?php if ($check){?></table><?php echo "<br>";} ?>

<div id="GeoColDiv" style="width:900px; height:450px;"></div>
  
<script src="../lib/OpenLayers/OpenLayers.js"></script>
<script>

    map = new OpenLayers.Map("GeoColDiv");
    map.addControl(new OpenLayers.Control.LayerSwitcher({'ascending':false}));
    map.addLayer(new OpenLayers.Layer.OSM('OSM'));
    epsg4326 =  new OpenLayers.Projection("EPSG:4326"); //WGS 1984 projection
    projectTo = map.getProjectionObject(); //The map projection (Spherical Mercator)
    
    var lonLat = new OpenLayers.LonLat(  <?php echo $mean_long . "," . $mean_lat;?> ).transform(epsg4326, projectTo);
     
    var zoom=15;
    map.setCenter (lonLat, zoom);
    var vectorLayer = new OpenLayers.Layer.Vector("Thumbnails");
    var vectorLayer2 = new OpenLayers.Layer.Vector("Markers");
    
    //Unloading values to Javascript, some cases require stripping
    //of backslashes because Javascript was complaining
    var markers = <?php echo str_replace(array('"','\\'),'',json_encode($markers)) ?>;
    var paths = <?php echo str_replace('\\','',json_encode($paths)) ?>;
	var baseurl = <?php echo str_replace('\\','',json_encode($baseurl) )?>;
    for (var i=0; i<markers.length; i++)
		{
        var lon = markers[i][0];
        var lat = markers[i][1];
        var rf = markers[i][2];
        var width = markers[i][3];
        var height = markers[i][4];
        var reslink = paths[i];
        
		var feature = new OpenLayers.Feature.Vector(
			new OpenLayers.Geometry.Point( lon, lat ).transform(epsg4326, projectTo),
			{description: baseurl +  '/pages/view.php?ref=' + rf},
			
			{externalGraphic: '..' + reslink, graphicHeight: height*0.45, graphicWidth: width*0.45, graphicXOffset:0, graphicYOffset:0 }
		);  
		var feature2 = new OpenLayers.Feature.Vector(
			new OpenLayers.Geometry.Point( lon, lat ).transform(epsg4326, projectTo),
			{description: baseurl +  '/pages/view.php?ref=' + rf},
			
			{externalGraphic: '../lib/OpenLayers/img/marker.png', graphicHeight: 25, graphicWidth: 21, graphicXOffset:0, graphicYOffset:0 }
		);  
		
		vectorLayer.addFeatures(feature);
		vectorLayer2.addFeatures(feature2);
		//Hide by default the default red marker and display
		//the thumbnails layer
		vectorLayer2.setVisibility(false)
		
	}
	
	vectorLayer.events.register("featureselected", null, function(event){
        var layer = event.feature.layer;
        ModalLoad(event.feature.attributes.description)
        selectControl.unselectAll();
		});
	vectorLayer2.events.register("featureselected", null, function(event){
        var layer = event.feature.layer;
        ModalLoad(event.feature.attributes.description)
        selectControl.unselectAll();
		});
    
    // Add select feature control required to trigger events on the vector layer.
    var selectControl = new OpenLayers.Control.SelectFeature(vectorLayer);
    map.addControl(selectControl);
    selectControl.activate(); 
    
    var selectControl2 = new OpenLayers.Control.SelectFeature(vectorLayer2);
    map.addControl(selectControl2);
    selectControl2.activate();   
    
    map.addLayer(vectorLayer);
    map.addLayer(vectorLayer2);

	
</script>


<?php
include "../include/footer.php";
?>
