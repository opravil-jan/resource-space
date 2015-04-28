<?php
/*
 * Dash Functions - Jethro, Montala Ltd
 * Functions for the homepage dash tiles
 * 
 */
function create_dash_tile($url,$link,$title,$reload_interval,$all_users,$default_order_by,$resource_count)
	{
	if($all_users && !((checkperm("h") && !checkperm("hdta")) || (checkperm("dta") && !checkperm("h")))){$all_users=false;}
	$rebuild_order=TRUE;

	# Validate Parameters
	if(empty($reload_interval) || !is_numeric($reload_interval))
		{$reload_interval=0;}
	
	$all_users=$all_users?1:0;

	if(!is_numeric($default_order_by))
		{
		$default_order_by=append_default_position();
		$rebuild_order=FALSE;
		}
	$resource_count = $resource_count?1:0;

	# De-Duplication of tiles on creation
	$existing = sql_query("SELECT ref FROM dash_tile WHERE url='".$url."' AND link='".$link."' AND title='".$title."' AND reload_interval_secs=".$reload_interval." AND all_users=".$all_users." AND resource_count=".$resource_count);
	if(isset($existing[0]["ref"]))
		{
		$tile=$existing[0]["ref"];
		$rebuild_order=FALSE;
		}
	else
		{
		$result = sql_query("INSERT INTO dash_tile (url,link,title,reload_interval_secs,all_users,default_order_by,resource_count) VALUES ('".$url."','".$link."','".$title."',".$reload_interval.",".$all_users.",".$default_order_by.",".$resource_count.")");
		$tile=sql_insert_id();
		}

	# If tile already existed then this no reorder
	if($rebuild_order){reorder_default_dash();}
	
	if($all_users==1)
		{
		$users = sql_query("SELECT ref FROM user");
		sql_query("START TRANSACTION");
		foreach($users as $user)
			{
			//New "all user" panels should be positioned at the start of their list rather than in the default position to highlight them to the user
			$result=add_user_dash_tile($user["ref"],$tile,5);
			if(!$result)
				{
				sql_query("ROLLBACK");
				return false;
				}
			}
		$result = sql_query("COMMIT");
		}
	return $tile;
	}

function delete_dash_tile($tile,$cascade=TRUE)
	{
	sql_query("DELETE FROM dash_tile WHERE ref='".$tile."'");
	if($cascade)
		{
		sql_query("DELETE FROM user_dash_tile WHERE dash_tile='".$tile."'");
		}
	}
function append_default_position()
	{
	$last_tile=sql_query("SELECT default_order_by from dash_tile order by default_order_by DESC LIMIT 1");
	return isset($last_tile[0]["default_order_by"])?$last_tile[0]["default_order_by"]+10:10;
	}
function reorder_default_dash()
	{
	$tiles = sql_query("SELECT ref FROM dash_tile WHERE all_users=1 ORDER BY default_order_by");
	$order_by=10 * count($tiles);
	for($i=count($tiles)-1;$i>=0;$i--)
		{
		$result = update_default_dash_tile_order($tiles[$i]["ref"],$order_by);
		$order_by-=10;
		}
	}
function update_default_dash_tile_order($tile,$order_by)
	{
	return sql_query("UPDATE dash_tile SET default_order_by='".$order_by."' WHERE ref='".$tile."'");
	}
function get_tile($tile)
 	{
 	$result=sql_query("SELECT * FROM dash_tile WHERE ref='".$tile."'");
 	return isset($result[0])?$result[0]:false;
 	}
function get_default_dash()
	{
	global $baseurl,$baseurl_short,$lang;
	#Build Tile Templates
	$tiles = sql_query("SELECT dash_tile.ref AS 'tile',dash_tile.title,dash_tile.url,dash_tile.reload_interval_secs,dash_tile.link,dash_tile.default_order_by as 'order_by' FROM dash_tile WHERE all_users=1 ORDER BY default_order_by");
	$order=10;
	if(count($tiles)==0){echo $lang["nodashtilefound"];exit;}
	foreach($tiles as $tile)
		{
		if($order != $tile["order_by"] || ($tile["order_by"] % 10) > 0){update_default_dash_tile_order($tile["tile"],$order);}
		$order+=10;
		?>
		<a href="<?php echo $baseurl."/".htmlspecialchars($tile["link"]);?>" onClick="if(dragging){dragging=false;e.defaultPrevented;}" class="HomePanel DashTile DashTileDraggable" id="tile<?php echo htmlspecialchars($tile["tile"]);?>">
			<div id="contents_tile<?php echo htmlspecialchars($tile["tile"]);?>" class="HomePanelIN HomePanelDynamicDash">
				<?php if (strpos($tile["url"],"dash_tile.php")!==false) {
                                # Only pre-render the title if using a "standard" tile and therefore we know the H2 will be in the target data.
                                ?>
                                <h2 class="title"><?php echo htmlspecialchars($tile["title"]);?></h2>
                                <?php } ?>
				<p>Loading...</p>
				<script>
					height = jQuery("#contents_tile<?php echo htmlspecialchars($tile["tile"]);?>").height();
					width = jQuery("#contents_tile<?php echo htmlspecialchars($tile["tile"]);?>").width();
					jQuery("#contents_tile<?php echo htmlspecialchars($tile["tile"]);?>").load("<?php echo $baseurl."/".$tile["url"]."&tile=".htmlspecialchars($tile["tile"]);?>&tlwidth="+width+"&tlheight="+height);
				</script>
			</div>
			
		</a>
		<?php
		}
		?>
	<div id="dash_tile_bin"><span class="dash_tile_bin_text"><?php echo $lang["tilebin"];?></span></div>
	<div id="delete_dialog" style="display:none;"></div>
	<script>
		function deleteDefaultDashTile(id) {
			jQuery.post( "<?php echo $baseurl?>/pages/ajax/dash_tile.php",{"tile":id,"delete":"true"},function(data){
				jQuery("#tile"+id).remove();
			});
		}
		function updateDashTileOrder(index,tile) {
			jQuery.post( "<?php echo $baseurl?>/pages/ajax/dash_tile.php",{"tile":tile,"new_index":((index*10)-5)});
		}
		var dragging=false;

		jQuery(function() {
		 	jQuery("#HomePanelContainer").sortable({
		  	  items: ".DashTileDraggable",
		  	  start: function(event,ui) {
		  	  	jQuery("#dash_tile_bin").show();
		  	  	dragging=true;
		  	  },
		  	  stop: function(event,ui) {
	          	jQuery("#dash_tile_bin").hide();
		  	  },
	          update: function(event, ui) {
	          	nonDraggableTiles = jQuery(".HomePanel").length - jQuery(".DashTileDraggable").length;
	          	newIndex = ui.item.index() - nonDraggableTiles;
	          	var id=jQuery(ui.item).attr("id").replace("tile","");
	          	updateDashTileOrder(newIndex,id);
	          }
		  	});
		    jQuery("#dash_tile_bin").droppable({
				accept: ".DashTileDraggable",
				activeClass: "ui-state-hover",
				hoverClass: "ui-state-active",
				drop: function(event,ui) {
					var id=jQuery(ui.draggable).attr("id");
					id = id.replace("tile","");
					title = jQuery(ui.draggable).find(".title").html();
					jQuery("#dash_tile_bin").hide();
					jQuery("#delete_dialog").dialog({
				    	title:'<?php echo $lang["dashtiledelete"]; ?>',
				    	modal: true,
						resizable: false,
						dialogClass: 'delete-dialog no-close',
				        buttons: {
				            "<?php echo $lang['confirmdefaultdashtiledelete'] ?>": function() {deleteDefaultDashTile(id); jQuery(this).dialog("close");},    
				            "<?php echo $lang['cancel'] ?>": function() { jQuery(this).dialog('close'); }
				        }
				    });
				}
	    	});
	  	});
	</script>
	<?php
	}



/*
 * User Dash Functions 
 */
function add_user_dash_tile($user,$tile,$order_by)
	{
	$reorder=TRUE;
	if(!is_numeric($user)||!is_numeric($tile)){return false;}
	if(!is_numeric($order_by))
		{
		$order_by=append_user_position($user);
		$reorder=FALSE;
		}
	$existing = sql_query("SELECT * FROM user_dash_tile WHERE user=".$user." AND dash_tile=".$tile);
	if(!$existing)
		{
		$result = sql_query("INSERT INTO user_dash_tile (user,dash_tile,order_by) VALUES (".$user.",".$tile.",".$order_by.")");
		}
	else
		{
		return $existing;
		}
	if($reorder){reorder_user_dash($user);}
	return true;
	}
 function get_user_tile($tile,$user)
 	{
 	$result=sql_query("SELECT * FROM user_dash_tile WHERE ref='".$tile."' AND user=".$user);
 	return isset($result[0])?$result[0]:false;
 	}
 function create_new_user_dash($user)
 	{
 	$tiles = sql_query("SELECT ref,default_order_by as 'order' FROM dash_tile WHERE all_users=1 ORDER BY default_order_by");
 	foreach($tiles as $tile)
 		{
 		add_user_dash_tile($user,$tile["ref"],$tile["order"]);
 		}
 	}
function update_user_dash_tile_order($user,$tile,$order_by)
	{
	return sql_query("UPDATE user_dash_tile SET order_by='".$order_by."' WHERE user='".$user."' and ref='".$tile."'");
	}

function delete_user_dash_tile($usertile,$user)
	{
	if(!is_numeric($usertile) || !is_numeric($user)){return false;}
	$row = sql_query("SELECT * from user_dash_tile WHERE ref=".$usertile." and user=".$user);
	sql_query("DELETE FROM user_dash_tile WHERE ref='".$usertile."' and user='".$user."'");
	$existing = sql_query("SELECT count(*) as 'count' FROM user_dash_tile WHERE ref='".$row["dash_tile"]."'");
	if($existing[0]["count"]<1)
		{
		delete_dash_tile($result["dash_tile"]);
		}
	}

function reorder_user_dash($user)
	{
	$user_tiles = sql_query("SELECT user_dash_tile.ref FROM user_dash_tile LEFT JOIN dash_tile ON user_dash_tile.dash_tile = dash_tile.ref WHERE user_dash_tile.user='".$user."' ORDER BY user_dash_tile.order_by");
	$order_by=10 * count($user_tiles);
	for($i=count($user_tiles)-1;$i>=0;$i--)
		{
		$result = update_user_dash_tile_order($user,$user_tiles[$i]["ref"],$order_by);
		$order_by-=10;
		}
	}

function append_user_position($user)
	{
	$last_tile=sql_query("SELECT order_by FROM user_dash_tile WHERE user='".$user."' ORDER BY order_by DESC LIMIT 1");
	return isset($last_tile[0]["order_by"])?$last_tile[0]["order_by"]+10:10;
	}

function get_user_dash($user)
	{
	global $baseurl,$baseurl_short,$lang;
	#Build User Dash and recalculate order numbers on display
	$user_tiles = sql_query("SELECT dash_tile.ref AS 'tile',dash_tile.title,dash_tile.all_users,dash_tile.url,dash_tile.reload_interval_secs,dash_tile.link,user_dash_tile.ref AS 'user_tile',user_dash_tile.order_by FROM user_dash_tile LEFT JOIN dash_tile ON user_dash_tile.dash_tile = dash_tile.ref WHERE user_dash_tile.user='".$user."' ORDER BY user_dash_tile.order_by");
	$order=10;
	foreach($user_tiles as $tile)
		{
		if($order != $tile["order_by"] || ($tile["order_by"] % 10) > 0){update_user_dash_tile_order($user,$tile["user_tile"],$order);}
		$order+=10;
		?>
		<a 
			href="<?php echo $baseurl."/".htmlspecialchars($tile["link"]);?>" 
			onClick="if(dragging){dragging=false;e.defaultPrevented}return CentralSpaceLoad(this,true);" 
			class="HomePanel DashTile DashTileDraggable <?php echo ($tile['all_users']==1)? 'allUsers':''; ?>"
			tile="<?php echo $tile['tile']; ?>"
			id="user_tile<?php echo htmlspecialchars($tile["user_tile"]);?>"
		>
			<div id="contents_user_tile<?php echo htmlspecialchars($tile["user_tile"]);?>" class="HomePanelIN HomePanelDynamicDash">
				<?php if (strpos($tile["url"],"dash_tile.php")!==false) {
                                # Only pre-render the title if using a "standard" tile and therefore we know the H2 will be in the target data.
                                ?>
                                <h2 class="title"><?php echo htmlspecialchars($tile["title"]);?></h2>
                                <?php } ?>
				<p>Loading...</p>
				<script>
				jQuery(function(){
					var height = jQuery("#contents_user_tile<?php echo htmlspecialchars($tile["user_tile"]);?>").height();
					var width = jQuery("#contents_user_tile<?php echo htmlspecialchars($tile["user_tile"]);?>").width();
					jQuery('#contents_user_tile<?php echo htmlspecialchars($tile["user_tile"]) ?>').load("<?php echo $baseurl."/".$tile["url"]."&tile=".htmlspecialchars($tile["tile"]);?>&user_tile=<?php echo htmlspecialchars($tile["user_tile"]);?>&tlwidth="+width+"&tlheight="+height);
				});
			</script>
			</div>
			
		</a>
		<?php
		}
	# Check Permissions to Display Deleting Dash Tiles
	if((checkperm("h") && !checkperm("hdta")) || (checkperm("dta") && !checkperm("h")) || !checkperm("dtu"))
		{ ?>
		<div id="dash_tile_bin"><span class="dash_tile_bin_text"><?php echo $lang["tilebin"];?></span></div>
		<div id="delete_dialog" style="display:none;"></div>
		<script>
			function deleteDashTile(id) {
				jQuery.post( "<?php echo $baseurl?>/pages/ajax/dash_tile.php",{"user_tile":id,"delete":"true"},function(data){
					jQuery("#user_tile"+id).remove();
				});
			}
			function deleteDefaultDashTile(tileid,usertileid) {
				jQuery.post( "<?php echo $baseurl?>/pages/ajax/dash_tile.php",{"tile":tileid,"delete":"true"},function(data){
					jQuery("#user_tile"+usertileid).remove();
				});
			}
		<?php
		}
	else
		{
		echo "<script>";
		} ?>
		function updateDashTileOrder(index,tile) {
			jQuery.post( "<?php echo $baseurl?>/pages/ajax/dash_tile.php",{"user_tile":tile,"new_index":((index*10)-5)});
		}
		var dragging=false;

		jQuery(function() {
		 	jQuery("#HomePanelContainer").sortable({
		  	  items: ".DashTileDraggable",
		  	  start: function(event,ui) {
		  	  	jQuery("#dash_tile_bin").show();
		  	  	dragging=true;
		  	  },
		  	  stop: function(event,ui) {
	          	jQuery("#dash_tile_bin").hide();
		  	  },
	          update: function(event, ui) {
	          	nonDraggableTiles = jQuery(".HomePanel").length - jQuery(".DashTileDraggable").length;
	          	newIndex = ui.item.index() - nonDraggableTiles;
	          	var id=jQuery(ui.item).attr("id").replace("user_tile","");
	          	updateDashTileOrder(newIndex,id);
	          }
		  	});
		<?php
		# Check Permissions to Display Deleting Dash Tiles
		if((checkperm("h") && !checkperm("hdta")) || (checkperm("dta") && !checkperm("h")) || !checkperm("dtu"))
			{
			?> 	
		    jQuery("#dash_tile_bin").droppable({
		      accept: ".DashTileDraggable",
		      activeClass: "ui-state-hover",
		      hoverClass: "ui-state-active",
		      drop: function(event,ui) {
		      	var id=jQuery(ui.draggable).attr("id");
		      	id = id.replace("user_tile","");
		    <?php
		    # If permission to delete all_user tiles
		    if((checkperm("h") && !checkperm("hdta")) || (checkperm("dta") && !checkperm("h")))
		    	{ ?>
		    	var tileid=jQuery(ui.draggable).attr("tile");
		    <?php
		      	} ?>

		      	title = jQuery(ui.draggable).find(".title").html();
		      	jQuery("#dash_tile_bin").hide();
	      	<?php
	      	# If permission to delete all_user tiles
			if((checkperm("h") && !checkperm("hdta")) || (checkperm("dta") && !checkperm("h")))
				{
				?>
		      	if(jQuery(ui.draggable).hasClass("allUsers")) {
		      		// This tile is set for all users so provide extra options
			        jQuery("#delete_dialog").dialog({
			        	title:'<?php echo $lang["dashtiledelete"]; ?>',
			        	modal: true,
	    				resizable: false,
    					dialogClass: 'delete-dialog no-close',
	                    buttons: {
	                        "<?php echo $lang['confirmdashtiledelete'] ?>": function() {deleteDashTile(id); jQuery(this).dialog( "close" );},
	                        "<?php echo $lang['confirmdefaultdashtiledelete'] ?>": function() {deleteDefaultDashTile(tileid,id); jQuery(this).dialog( "close" );},
	                        "<?php echo $lang['managedefaultdash'] ?>": function() {window.location = "<?php echo $baseurl; ?>/pages/team/team_dash_tile.php"; return false;},
	                        "<?php echo $lang['cancel'] ?>":  function() { jQuery(this).dialog('close'); }
	                    }
	                });
	            }
	            else {
	            	//This tile belongs to this user
			        jQuery("#delete_dialog").dialog({
			        	title:'<?php echo $lang["dashtiledelete"]; ?>',
			        	modal: true,
	    				resizable: false,	    				
    					dialogClass: 'delete-dialog no-close',
	                    buttons: {
	                        "<?php echo $lang['confirmdashtiledelete'] ?>": function() {deleteDashTile(id); jQuery(this).dialog( "close" );},
	                        "<?php echo $lang['cancel'] ?>": function() { jQuery(this).dialog('close'); }
	                    }
	                });
	            }
            <?php
            	}
       		else #Only show dialog to delete for this user
       			{ ?>
       			var dialog = jQuery("#delete_dialog").dialog({
		        	title:'<?php echo $lang["dashtiledelete"]; ?>',
		        	modal: true,
    				resizable: false,
    				dialogClass: 'delete-dialog no-close',
                    buttons: {
                        "<?php echo $lang['confirmdashtiledelete'] ?>": function() {deleteDashTile(id); jQuery(this).dialog( "close" );},
                        "<?php echo $lang['cancel'] ?>": function() {jQuery(this).dialog('close'); }
                    }
                });
		    <?php
       			} ?>
		      }
	    	});
	    	<?php
    		} 
    	?>
	  	});
	</script>
	<?php
	}
