<?php


function HookResource_tourGeo_searchGeosearch()
	{
	
		?>
<table class="InfoTable">
			<tr>
			<td><div id="ValueText">Search radius around me [meters]:</div></td>
			<td><input id="radius" type="range" min="<?php global $min_val;echo $min_val;?>" max="<?php global $max_val;echo $max_val;?>" step="<?php global $step;echo $step;?>" value="500" onmousemove="showrangevalue();"/></td>
			<td><div id="value"><?php global $default_val;echo $default_val;?></div></td>
			
			</tr>
			<tr>
			<td><div id="ResultsCount">Resources Found:</div></td>
			<td><div id="TourResultCount"> </div>	</td>
			<td><button id="locate">Locate me and fetch resources!</button></td>
			
			</tr>
</table>
			

<input type="checkbox" name="track" id="track">
        <label for="track">Follow me!</label>
<div id="LoadingBox"></div>
<script type="text/javascript">
document.getElementById("dragmodepan").checked = true;
//jQuery('#ResultsCount').hide();	

var uurl = "<?php global $baseurl;echo $baseurl;?>"+"/plugins/resource_tour/ajax/nearby_resources.php";

function showrangevalue(){
  document.getElementById("value").innerHTML=document.getElementById("radius").value;
}
function showTourResultCount(count){
  document.getElementById("TourResultCount").innerHTML=count;
}
projectTo = map.getProjectionObject(); 
var vector = new OpenLayers.Layer.Vector('Resource Tour!');
var vectorLayer = new OpenLayers.Layer.Vector("Thumbnails");
var vectorLayer2 = new OpenLayers.Layer.Vector("Markers");
map.addLayers([vector]);


var geolocate = new OpenLayers.Control.Geolocate({
	bind: false,
	geolocationOptions: {
		enableHighAccuracy: false,
		maximumAge: 0,
		timeout: 5000
	}
});

map.addControl(geolocate);

geolocate.events.register("locationupdated",geolocate,function(e) {
		jQuery('#LoadingBox').show();
		
		var radius 	    = parseFloat(document.getElementById('radius').value);

		var geographic  = new OpenLayers.Projection("EPSG:4326");
		var mercator    = new OpenLayers.Projection("EPSG:900913");
		var MyPos       = new OpenLayers.Geometry.Point(e.point.x, e.point.y).transform(mercator,geographic);
		var x = parseFloat(e.point.x);
		var y = parseFloat(e.point.y);
		
		var MyPosPer    = new OpenLayers.Geometry.Point(x, y);

		var Bound_South = new OpenLayers.Geometry.Point(x, y-radius).transform(mercator,geographic);
		var Bound_North = new OpenLayers.Geometry.Point(x, y+radius).transform(mercator,geographic);
		
		var Bound_West  = new OpenLayers.Geometry.Point(x-radius, y).transform(mercator,geographic);
		var Bound_East  = new OpenLayers.Geometry.Point(x+radius, y).transform(mercator,geographic);

		function ajaxCallBack(data){
			var markers = data;
			return markers;
		}

		
		jQuery.ajax({
		type:'POST',
		url:uurl,
		dataType: 'json',				  
		data: {
			  jsonData: JSON.stringify({
				  "coord": MyPos,"Bound_East":Bound_East,"Bound_West":Bound_West,"Bound_North":Bound_North,"Bound_South":Bound_South,"Radius":radius,
			  })} ,
		
		success: function(data)          
		{
			if (data==='cows go moo when they poo'){
				
				jQuery('#LoadingBox').hide();
				alert('No resources found!');
			}
			
			else
			{
			
			//jQuery('#TourResultCount').show();
			jQuery('#ResultsCount').show();	
			showTourResultCount(data.length);
			
			vector.removeAllFeatures();
			vectorLayer.removeAllFeatures();
			vectorLayer2.removeAllFeatures();
			ajaxCallBack(data);
			
			var me = new OpenLayers.Feature.Vector(
					e.point,
					{},
					{	externalGraphic:'../plugins/resource_tour/gfx/marker.png', graphicHeight: 25, graphicWidth: 15
						//externalGraphic: '../lib/OpenLayers/img/marker.png', graphicHeight: 25, graphicWidth: 21 ,
						
					}
				);
			var circle = new OpenLayers.Feature.Vector(
				new  OpenLayers.Geometry.Polygon.createRegularPolygon( MyPosPer ,radius, 150,0),null,{fillColor: '#ea2828',
				strokeColor: '#ea2828',
				strokeOpacity: 1,
				strokeWidth: 4,
				graphicZIndex: 1099,
				fillOpacity: 0});
			
			vector.addFeatures([circle,me]);
			
			
			for (var i=0; i<data.length; i++)
				{
				//alert(i);
				//console.log(data.length);	
				var lon = data[i].lon;
				//alert(lon);
				var lat = data[i].lat;
				var rf = data[i].res;
				var width = data[i].thumbwidth;
				var height = data[i].thumbheight;
				var reslink = data[i].url;
				
				
				var feature = new OpenLayers.Feature.Vector(
					new OpenLayers.Geometry.Point( lon, lat ).transform(geographic, projectTo),
					{description: baseurl +  '/pages/view.php?ref=' + rf},
					{externalGraphic: '..' + reslink, graphicHeight: height*0.45, graphicWidth: width*0.45 }
				);
				
				var feature2 = new OpenLayers.Feature.Vector(
					new OpenLayers.Geometry.Point( lon, lat ).transform(geographic, projectTo),
					{description: baseurl +  '/pages/view.php?ref=' + rf},
					{externalGraphic: '../lib/OpenLayers/img/marker.png', graphicHeight: 25, graphicWidth: 21 }
				);  
				
				vectorLayer.addFeatures(feature);
				vectorLayer2.addFeatures(feature2);
				
			}
			//Hide by default the thumbnails and display markers
			vectorLayer.setVisibility(false);		
		
			vectorLayer.events.register("featureselected", null, function(event){
				ModalLoad(event.feature.attributes.description);
				selectControl.unselectAll();
				});
		
			vectorLayer2.events.register("featureselected", null, function(event){
				ModalLoad(event.feature.attributes.description);
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
			
			map.zoomToExtent(vector.getDataExtent());
			jQuery('#LoadingBox').hide();
			}
			  
		}
		});
	  
	}
	
	);


geolocate.events.register("locationfailed",this,function() {
    OpenLayers.Console.log('Location detection failed');
});


document.getElementById('locate').onclick = function() {
    geolocate.deactivate();
    //firstGeolocation = true;
    geolocate.activate();
};

</script>


<?php }
?>