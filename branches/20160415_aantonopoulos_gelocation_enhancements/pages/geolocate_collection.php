<?php
include "../include/db.php";
include_once "../include/general.php";
include "../include/authenticate.php"; 
include "../include/resource_functions.php";
include "../include/collections_functions.php";
include "../include/header.php";


global $baseurl;

$ref=getvalescaped("ref","",true);
$all_resources =  get_collection_resources($ref) ;
$collection =  get_collection($ref);
$collectionname = $collection['name'];
$markers = array();
$mean_lat=0;
$mean_long=0;

foreach ($all_resources as $value) {
    
    $resource = get_resource_data($value,$cache=true);
    $url = get_resource_path($resource['ref'],false,"col",$generate=true,$extension="jpg",$scramble=-1,$page=1,$watermarked=false,$file_modified="",$alternative=-1,$includemodified=true);
	$new = str_replace($baseurl,"", $url);
	$parts =  explode('?',$new);
    $markers[] =  [ $resource['geo_long'] . "," .  $resource['geo_lat'] . "," . $resource['ref'] ];
    $paths[] = $parts[0];
    $mean_long=$mean_long+$resource['geo_long'];
    $mean_lat=$mean_lat+$resource['geo_lat']; 
    }

$mean_lat=$mean_lat/count($markers);
$mean_long=$mean_long/count($markers);

?>

<h1><?php echo $lang["geolocatecollection"] ?></h1>
<h3><?php echo $lang["collectionname"] . ": " . $collectionname  ?></h3>

<div id="GeoColDiv" style="width:700px; height:300px;"></div>
  
<script src="http://www.openlayers.org/api/OpenLayers.js"></script>
<script>

    map = new OpenLayers.Map("GeoColDiv");
    map.addLayer(new OpenLayers.Layer.OSM());
    epsg4326 =  new OpenLayers.Projection("EPSG:4326"); //WGS 1984 projection
    projectTo = map.getProjectionObject(); //The map projection (Spherical Mercator)
   
    var lonLat = new OpenLayers.LonLat(  <?php echo $mean_long . "," . $mean_lat;?> ).transform(epsg4326, projectTo);
          
    var zoom=14;
    map.setCenter (lonLat, zoom);
    var vectorLayer = new OpenLayers.Layer.Vector("Overlay");
    var markers = <?php echo str_replace(array('"','\\'),'',json_encode($markers)) ?>;
    var paths = <?php echo str_replace('\\','',json_encode($paths)) ?>;
	var baseurl = <?php echo str_replace('\\','',json_encode($baseurl) )?>;
    for (var i=0; i<markers.length; i++) {
      
       var lon = markers[i][0];
       var lat = markers[i][1];
       var rf = String(markers[i][2]);
       var reslink = paths[i];
       var feature = new OpenLayers.Feature.Vector(
                new OpenLayers.Geometry.Point( lon, lat ).transform(epsg4326, projectTo),
                {description: baseurl +  '/pages/view.php?ref=' + rf},
                {externalGraphic: '..' + reslink, graphicHeight: 45, graphicWidth: 41, graphicXOffset:-32, graphicYOffset:-45 }
            );   
		      
    
    vectorLayer.addFeatures(feature);

    vectorLayer.events.register("featureselected", null, function(event){
        var layer = event.feature.layer;
        ModalLoad(event.feature.attributes.description)
    });
    
    // Add select feature control required to trigger events on the vector layer.
    var selectControl = new OpenLayers.Control.SelectFeature(vectorLayer);
    map.addControl(selectControl);
    selectControl.activate(); 
        
    }     
    map.addLayer(vectorLayer);


</script>


<?php
include "../include/footer.php";
?>