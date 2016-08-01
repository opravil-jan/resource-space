<?php

function HookResource_tourGeo_searchGeosearch()
	{
		?>
				
<button id="locate">Locate me!</button>
<input id="radius" type ="range" min ="1000" max="100000" step ="50" value ="1000"/>

        <input type="checkbox" name="track" id="track">
        <label for="track">Follow me!</label>
<div id="LoadingBox"></div>
<script>

//epsg4326 =  new OpenLayers.Projection("EPSG:4326"); //WGS 1984 projection
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
var firstGeolocation = true;

geolocate.events.register("locationupdated",geolocate,function(e) {
		var radius 	    = parseFloat(document.getElementById('radius').value);
		//alert(radius);
		var geographic  = new OpenLayers.Projection("EPSG:4326");
		var mercator    = new OpenLayers.Projection("EPSG:900913");
		var MyPos       = new OpenLayers.Geometry.Point(e.point.x, e.point.y).transform(mercator,geographic);
		var x = parseFloat(e.point.x);
		var y = parseFloat(e.point.y);
		
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
				  url: 'https://localhost/resourcespace/rs/branches/Geo/plugins/resource_tour/ajax/nearby_resources.php',            
				  dataType: 'json',				  
				  data: {
						jsonData: JSON.stringify({
							"coord": MyPos,"Bound_East":Bound_East,"Bound_West":Bound_West,"Bound_North":Bound_North,"Bound_South":Bound_South,
						})} ,
				  
				  success: function(data)          
				  {
					//alert(data);
					ajaxCallBack(data);
					alert(data);
					jQuery('#LoadingBox').hide();
					var me = new OpenLayers.Feature.Vector(
						new OpenLayers.Geometry.Point( e.point.x, e.point.y ).transform(geographic, projectTo)
						);
					
					vector.addFeatures(new OpenLayers.Feature.Vector(
						e.point
						),
						me
						);
					
					
					
					
					
					
					
					
					
					
					map.zoomToExtent(vector.getDataExtent());
						
				  }
				});
			  
			}
			
			);


geolocate.events.register("locationfailed",this,function() {
    OpenLayers.Console.log('Location detection failed');
});


document.getElementById('locate').onclick = function() {
    vector.removeAllFeatures();
    geolocate.deactivate();
    firstGeolocation = true;
    geolocate.activate();
};

</script>


<?php }
?>