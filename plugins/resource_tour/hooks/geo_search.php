<?php


function HookResource_tourGeo_searchGeosearch()
	{
		?>
				
Search radius around me [meters]:<div id="value">500</div>
<input id="radius" type="range" min="50" max="100000" step="50" value="500" onmousemove="showrangevalue();"/>
<button id="locate">Locate me and fetch resources!</button>
<input type="checkbox" name="track" id="track">
        <label for="track">Follow me!</label>
<div id="LoadingBox"></div>
<script type="text/javascript">
	
var uurl = "<?php global $baseurl;echo $baseurl;?>"+"/plugins/resource_tour/ajax/nearby_resources.php";

function showrangevalue(){
  document.getElementById("value").innerHTML=document.getElementById("radius").value;
}

projectTo = map.getProjectionObject(); 
var vector = new OpenLayers.Layer.Vector('Resource Tour!');
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
		var radius 	    = parseFloat(document.getElementById('radius').value);
		//alert(radius);
		var geographic  = new OpenLayers.Projection("EPSG:4326");
		var mercator    = new OpenLayers.Projection("EPSG:900913");
		var MyPos       = new OpenLayers.Geometry.Point(e.point.x, e.point.y).transform(mercator,geographic);
		var x = parseFloat(e.point.x);
		var y = parseFloat(e.point.y);
		//Slightly perturb user's point, can not use identical points for some reason 
		var MyPosPer    = new OpenLayers.Geometry.Point(x+0.000001, e.point.y);
		//alert(MyPosPer);
		var Bound_South = new OpenLayers.Geometry.Point(x, y-radius).transform(mercator,geographic);
		var Bound_North = new OpenLayers.Geometry.Point(x, y+radius).transform(mercator,geographic);
		
		var Bound_West  = new OpenLayers.Geometry.Point(x-radius, y).transform(mercator,geographic);
		var Bound_East  = new OpenLayers.Geometry.Point(x+radius, y).transform(mercator,geographic);

		//alert(Bound_South);
		
		
		
		//var retVal = null; 
		
		function ajaxCallBack(data){
			var markers = data;
			return markers;
		}

		jQuery('#LoadingBox').show();
		jQuery.ajax({
		type:'POST',
		url:uurl,
		//url: 'https://localhost/resourcespace/rs/branches/Geo/plugins/resource_tour/ajax/nearby_resources.php',            
		dataType: 'json',				  
		data: {
			  jsonData: JSON.stringify({
				  "coord": MyPos,"Bound_East":Bound_East,"Bound_West":Bound_West,"Bound_North":Bound_North,"Bound_South":Bound_South,
			  })} ,
		
		success: function(data)          
		{
			//alert(data);
			vector.removeAllFeatures();
			ajaxCallBack(data);
			//alert(data);
			jQuery('#LoadingBox').hide();
			//var me = new OpenLayers.Geometry.Point( e.point.x, e.point.y ).transform(geographic, projectTo);
			//alert(e.point.x);
			var me = new OpenLayers.Feature.Vector(
					e.point,
					{},
					{	externalGraphic:'../plugins/resource_tour/gfx/marker.png', graphicHeight: 25, graphicWidth: 15
						//externalGraphic: '../lib/OpenLayers/img/marker.png', graphicHeight: 25, graphicWidth: 21 ,
						
					}
				);
			
		
			var circle = new OpenLayers.Feature.Vector(
				new  OpenLayers.Geometry.Polygon.createRegularPolygon( MyPosPer ,radius, 50,0),null,{fillColor: '#ea2828',
				strokeColor: '#ea2828',
				strokeOpacity: 1,
				strokeWidth: 4,
				graphicZIndex: 1099,
				fillOpacity: 0});
			
			vector.addFeatures([circle,me]);
			
			map.zoomToExtent(vector.getDataExtent());
			  
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