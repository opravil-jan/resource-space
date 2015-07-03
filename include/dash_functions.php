<?php
/*
 * Dash Functions - Jethro, Montala Ltd
 * Functions for the homepage dash tiles
 * 
 */

/*
 * Create a dash tile template
 * @$all_users, 
 *	If passed true will push the tile out to all users in your installation
 *  If passed false you must give this tile to a user with sql_insert_id() to have it used
 * 
 */
function create_dash_tile($url,$link,$title,$reload_interval,$all_users,$default_order_by,$resource_count,$text="",$delete=1)
	{
	
	$rebuild_order=TRUE;

	# Validate Parameters
	if(empty($reload_interval) || !is_numeric($reload_interval))
		{$reload_interval=0;}

	$delete = $delete?1:0;
	$all_users=$all_users?1:0;

	if(!is_numeric($default_order_by))
		{
		$default_order_by=append_default_position();
		$rebuild_order=FALSE;
		}
	$resource_count = $resource_count?1:0;

	# De-Duplication of tiles on creation
	$existing = sql_query("SELECT ref FROM dash_tile WHERE url='".$url."' AND link='".$link."' AND title='".escape_check($title)."' AND txt='".escape_check($text)."' AND reload_interval_secs=".$reload_interval." AND all_users=".$all_users." AND resource_count=".$resource_count);
	if(isset($existing[0]["ref"]))
		{
		$tile=$existing[0]["ref"];
		$rebuild_order=FALSE;
		}
	else
		{
		$result = sql_query("INSERT INTO dash_tile (url,link,title,reload_interval_secs,all_users,default_order_by,resource_count,allow_delete,txt) VALUES ('".$url."','".$link."','".escape_check($title)."',".$reload_interval.",".$all_users.",".$default_order_by.",".$resource_count.",".$delete.",'".escape_check($text)."')");
		$tile=sql_insert_id();
		}

	# If tile already existed then this no reorder
	if($rebuild_order){reorder_default_dash();}
	
	if($all_users==1)
		{
		$result = sql_query("INSERT user_dash_tile (user,dash_tile,order_by) SELECT user.ref,'".$tile."',5 FROM user");
		}
	return $tile;
	}

/*
 * Delete a dash tile
 * @$tile, the dash_tile.ref number of the tile to be deleted
 * @$cascade, whether this delete should remove the tile from all users.
 */
function delete_dash_tile($tile,$cascade=TRUE)
	{
	sql_query("DELETE FROM dash_tile WHERE ref='".$tile."' AND allow_delete=1");
	if($cascade)
		{
		sql_query("DELETE FROM user_dash_tile WHERE dash_tile='".$tile."'");
		}
	}

/*
 * Returns the position to append a tile to the default dash order
 */
function append_default_position()
	{
	$last_tile=sql_query("SELECT default_order_by from dash_tile order by default_order_by DESC LIMIT 1");
	return isset($last_tile[0]["default_order_by"])?$last_tile[0]["default_order_by"]+10:10;
	}

/*
 * Reorders the default dash,
 * this is useful when you have just inserted a new tile or moved a tile and need to reorder them with the proper 10 gaps 
 * Tiles should be ordered with values 10,20,30,40,50,60,70 for easy insertion
 */
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
/*
 * Simple updates a particular dash_tile with the new order_by.
 * this does NOT apply to a users dash, that must done with the user_dash functions.
 */
function update_default_dash_tile_order($tile,$order_by)
	{
	return sql_query("UPDATE dash_tile SET default_order_by='".$order_by."' WHERE ref='".$tile."'");
	}
/*
 * Gets the full content from a tile record row
 *
 */
function get_tile($tile)
 	{
 	$result=sql_query("SELECT * FROM dash_tile WHERE ref='".$tile."'");
 	return isset($result[0])?$result[0]:false;
 	}

/*
 * Checks if a tile already exists.
 * This is based upon  a complete set of values so unless all values match exactly it will return false.
 *
 */
function existing_tile($title,$all_users,$url,$link,$reload_interval,$resource_count,$text="")
	{
	$sql = "SELECT ref FROM dash_tile WHERE url='".$url."' AND link='".$link."' AND title='".escape_check($title)."' AND reload_interval_secs=".$reload_interval." AND all_users=".$all_users." AND resource_count=".$resource_count." AND txt='".escape_check($text)."'";
	$existing = sql_query($sql);
	if(isset($existing[0]["ref"]))
		{
		return true;
		}
	else
		{
		return false;
		}
	}
/*
 * Retrieves the default dash which only display all_user tiles.
 * This should only be accessible to thos with Dash Tile Admin permissions
 */
function get_default_dash()
	{
	global $baseurl,$baseurl_short,$lang,$anonymous_login,$username,$dash_tile_shadows;
	#Build Tile Templates
	$tiles = sql_query("SELECT dash_tile.ref AS 'tile',dash_tile.title,dash_tile.url,dash_tile.reload_interval_secs,dash_tile.link,dash_tile.default_order_by as 'order_by' FROM dash_tile WHERE dash_tile.all_users=1 AND (dash_tile.allow_delete=1 OR (dash_tile.allow_delete=0 AND dash_tile.ref IN (SELECT DISTINCT user_dash_tile.dash_tile FROM user_dash_tile))) ORDER BY default_order_by");
	$order=10;
	if(count($tiles)==0){echo $lang["nodashtilefound"];exit;}
	foreach($tiles as $tile)
		{
		if($order != $tile["order_by"] || ($tile["order_by"] % 10) > 0){update_default_dash_tile_order($tile["tile"],$order);}
		$order+=10;
		?>
		<a 
			<?php 
			# Check link for external or internal
			if(mb_strtolower(substr($tile["link"],0,4))=="http")
				{
				$link = $tile["link"];
				$newtab = true;
				}
			else
				{
				$link = $baseurl."/".htmlspecialchars($tile["link"]);
				$newtab=false;
				}
			?>
			href="<?php echo $link?>" <?php echo $newtab ? "target='_blank'" : "";?>
			onClick="if(dragging){dragging=false;e.defaultPrevented;}" 
			class="HomePanel DashTile DashTileDraggable" 
			id="tile<?php echo htmlspecialchars($tile["tile"]);?>"
		>
			<div id="contents_tile<?php echo htmlspecialchars($tile["tile"]);?>" class="HomePanelIN HomePanelDynamicDash <?php echo ($dash_tile_shadows)? "TileContentShadow":"";?>">
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
				jQuery.post( "<?php echo $baseurl?>/pages/ajax/dash_tile.php",{"tile":tile,"new_index":((index*10))});
			}
			var dragging=false;
				jQuery(function() {
					if(jQuery(window).width()<600 && jQuery(window).height()<600 && is_touch_device()) {
						jQuery("#HomePanelContainer").prepend("<p><?php echo $lang["dashtilesmalldevice"];?></p>");
						return false;
					}
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
	<div class="clearerleft"></div>
	<?php
	}
/*
 * Shows only tiles that are marked for all_users (and displayed on a user dash if they are a legacy tile).
 * No controls to modify or reorder (See $managed_home_dash config option)
 */
function get_managed_dash()
	{
	global $baseurl,$baseurl_short,$lang,$anonymous_login,$username,$dash_tile_shadows;
	#Build Tile Templates
	$tiles = sql_query("SELECT dash_tile.ref AS 'tile',dash_tile.title,dash_tile.url,dash_tile.reload_interval_secs,dash_tile.link,dash_tile.default_order_by as 'order_by' FROM dash_tile WHERE dash_tile.all_users=1 AND (dash_tile.allow_delete=1 OR (dash_tile.allow_delete=0 AND dash_tile.ref IN (SELECT DISTINCT user_dash_tile.dash_tile FROM user_dash_tile))) ORDER BY default_order_by");
	foreach($tiles as $tile)
		{
		?>
		<a 
			<?php 
			# Check link for external or internal
			if(mb_strtolower(substr($tile["link"],0,4))=="http")
				{
				$link = $tile["link"];
				$newtab = true;
				}
			else
				{
				$link = $baseurl."/".htmlspecialchars($tile["link"]);
				$newtab=false;
				}
			?>
			href="<?php echo $link?>" <?php echo $newtab ? "target='_blank'" : "";?>
			onClick="if(dragging){dragging=false;e.defaultPrevented;}" 
			class="HomePanel DashTile DashTileDraggable" 
			id="tile<?php echo htmlspecialchars($tile["tile"]);?>"
		>
			<div id="contents_tile<?php echo htmlspecialchars($tile["tile"]);?>" class="HomePanelIN HomePanelDynamicDash <?php echo ($dash_tile_shadows)? "TileContentShadow":"";?>">
				<?php if (strpos($tile["url"],"dash_tile.php")!==false) 
					{
                    # Only pre-render the title if using a "standard" tile and therefore we know the H2 will be in the target data.
                    ?>
                    <h2 class="title"><?php echo htmlspecialchars($tile["title"]);?></h2>
                    <?php 
                	} ?>
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
	<div class="clearerleft"></div>
	<?php
	}


/*
 * User Group dash functions
 *
 */
function add_usergroup_dash_tile($usergroup)
	{

	}
function get_usergroup_dash($usergroup)
	{

	}

/*
 * User Dash Functions 
 */

/*
 * Add a tile to a users dash
 * Affects the user_dash_tile table, tile must be the ref of a record from dash_tile
 *
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

/*
 * Get user_dash_tile record, 
 * Provide the user_dash_tile ref as the $tile
 * this a place holder which links a dash_tile template with the user and the order that that tile should appear on THIS users dash
 *
 */
 function get_user_tile($usertile,$user)
 	{
 	$result=sql_query("SELECT * FROM user_dash_tile WHERE ref='".escape_check($usertile)."' AND user=".escape_check($user));
 	return isset($result[0])?$result[0]:false;
 	}

 /*
  * Builds a users dash, this is a quick way of adding all_user tiles back to a users dash. 
  * The Add_user_dash_tile function used checks for an existing match so that it won't duplicate tiles on a users dash
  * 
  */
 function create_new_user_dash($user)
 	{
 	$tiles = sql_query("SELECT dash_tile.ref as 'tile',dash_tile.title,dash_tile.url,dash_tile.reload_interval_secs,dash_tile.link,dash_tile.default_order_by as 'order' FROM dash_tile WHERE dash_tile.all_users=1 AND (dash_tile.allow_delete=1 OR (dash_tile.allow_delete=0 AND dash_tile.ref IN (SELECT DISTINCT user_dash_tile.dash_tile FROM user_dash_tile))) ORDER BY default_order_by");
 	foreach($tiles as $tile)
 		{
 		add_user_dash_tile($user,$tile["tile"],$tile["order"]);
 		}
 	}

/*
 * Updates a user_dash_tile record for a specific tile on a users dash with an order.
 *
 */
function update_user_dash_tile_order($user,$tile,$order_by)
	{
	return sql_query("UPDATE user_dash_tile SET order_by='".escape_check($order_by)."' WHERE user='".escape_check($user)."' and ref='".$tile."'");
	}
/*
 * Delete a tile from a user dash
 * this will only remove the tile from this users dash. 
 * It must be the ref of the row in the user_dash_tile
 * this also performs cleanup to ensure that there are no unused templates in the dash_tile table
 *
 */
function delete_user_dash_tile($usertile,$user)
	{
	if(!is_numeric($usertile) || !is_numeric($user)){return false;}
	
	$row = get_user_tile($usertile,$user);
	sql_query("DELETE FROM user_dash_tile WHERE ref='".$usertile."' and user='".$user."'");

	$existing = sql_query("SELECT count(*) as 'count' FROM user_dash_tile WHERE dash_tile='".$row["dash_tile"]."'");
	if($existing[0]["count"]<1)
		{
		delete_dash_tile($row["dash_tile"]);
		}
	}

/*
 * Remove all tiles from a users dash
 * Purge option does the cleanup in dash_tile removing any unused tiles
 * Turn purge off if you are just doing a quick rebuild of the tiles.
 */
function empty_user_dash($user,$purge=true)
	{
	$usertiles = sql_query("SELECT dash_tile FROM user_dash_tile WHERE user_dash_tile.user='".escape_check($user)."'");
	sql_query("DELETE FROM user_dash_tile WHERE user='".$user."'");
	if($purge)
		{
		foreach($usertiles as $tile)
			{
			$existing = sql_query("SELECT count(*) as 'count' FROM user_dash_tile WHERE dash_tile='".$tile["dash_tile"]."'");
			if($existing[0]["count"]<1)
				{
				delete_dash_tile($tile["dash_tile"]);
				}
			}
		}	
	}


/*
 * Reorders the users dash,
 * this is useful when you have just inserted a new tile or moved a tile and need to reorder them with the proper 10 gaps 
 * Tiles should be ordered with values 10,20,30,40,50,60,70 for easy insertion
 */
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

/*
 * Returns the position for a tile at the end of existing tiles
 *
 */
function append_user_position($user)
	{
	$last_tile=sql_query("SELECT order_by FROM user_dash_tile WHERE user='".$user."' ORDER BY order_by DESC LIMIT 1");
	return isset($last_tile[0]["order_by"])?$last_tile[0]["order_by"]+10:10;
	}

/*
 * All dash tiles available to the supplied userref
 * If you provide a dash_tile ref it will check if this tile exists within the list of available tiles to the user
 *
 */
function get_user_available_tiles($user,$tile="null")
	{
	$tilecheck = (is_numeric($tile)) ? "WHERE ref='".$tile."'":"";
	return sql_query
		(
			"
			SELECT 
				result.*
			FROM
			(	(
				SELECT 
					dash_tile.ref,
					'' as 'dash_tile',
					'' as 'usertile', 
					'' as 'user', 
					'' as 'order_by',
					dash_tile.ref as 'tile',
					dash_tile.title,
					dash_tile.txt,
					dash_tile.link,
					dash_tile.resource_count,
					dash_tile.all_users,
					dash_tile.default_order_by
				FROM
					dash_tile
				WHERE
					dash_tile.all_users = 1
					AND
					ref 
					NOT IN
					(
						SELECT 
							dash_tile.ref
						FROM
							user_dash_tile
						RIGHT OUTER JOIN
							dash_tile
						ON 
							user_dash_tile.dash_tile = dash_tile.ref

						WHERE
							user_dash_tile.user = '".$user."'
					)
				)
			UNION
				(
				SELECT 
					dash_tile.ref,
					user_dash_tile.dash_tile,
					user_dash_tile.ref as 'usertile', 
					user_dash_tile.user, 
					user_dash_tile.order_by,
					dash_tile.ref as 'tile',
					dash_tile.title,
					dash_tile.txt,
					dash_tile.link,
					dash_tile.resource_count,
					dash_tile.all_users,
					dash_tile.default_order_by
				FROM
					user_dash_tile
				RIGHT OUTER JOIN
					dash_tile
				ON 
					user_dash_tile.dash_tile = dash_tile.ref
				WHERE
					user_dash_tile.user = '".$user."'
				)
			) result
			".$tilecheck."
			ORDER BY result.order_by,result.default_order_by

			"
		);
	}

/*
 * Returns a users dash along with all necessary scripts and tools for manipulation
 * checks for the permissions which allow for deletions and manipulation of all_user tiles from the dash
 *
 */
function get_user_dash($user)
	{
	global $baseurl,$baseurl_short,$lang,$dash_tile_shadows;
	#Build User Dash and recalculate order numbers on display
	$user_tiles = sql_query("SELECT dash_tile.ref AS 'tile',dash_tile.title,dash_tile.all_users,dash_tile.url,dash_tile.reload_interval_secs,dash_tile.link,user_dash_tile.ref AS 'user_tile',user_dash_tile.order_by FROM user_dash_tile JOIN dash_tile ON user_dash_tile.dash_tile = dash_tile.ref WHERE user_dash_tile.user='".$user."' ORDER BY user_dash_tile.order_by");

	$order=10;
	foreach($user_tiles as $tile)
		{
		if($order != $tile["order_by"] || ($tile["order_by"] % 10) > 0){update_user_dash_tile_order($user,$tile["user_tile"],$order);}
		$order+=10;
		?>
		<a 
			<?php 
			# Check link for external or internal
			if(mb_strtolower(substr($tile["link"],0,4))=="http")
				{
				$link = $tile["link"];
				$newtab = true;
				}
			else
				{
				$link = $baseurl."/".htmlspecialchars($tile["link"]);
				$newtab=false;
				}
			?>
			href="<?php echo $link?>" <?php echo $newtab ? "target='_blank'" : "";?> 
			onClick="if(dragging){dragging=false;e.defaultPrevented}<?php echo $newtab? "": "return CentralSpaceLoad(this,true);";?>" 
			class="HomePanel DashTile DashTileDraggable <?php echo ($tile['all_users']==1)? 'allUsers':'';?>"
			tile="<?php echo $tile['tile']; ?>"
			id="user_tile<?php echo htmlspecialchars($tile["user_tile"]);?>"
		>
			<div id="contents_user_tile<?php echo htmlspecialchars($tile["user_tile"]);?>" class="HomePanelIN HomePanelDynamicDash <?php echo ($dash_tile_shadows)? "TileContentShadow":"";?>">                  
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
			jQuery.post( "<?php echo $baseurl?>/pages/ajax/dash_tile.php",{"user_tile":tile,"new_index":((index*10))});
		}
		var dragging=false;
			jQuery(function() {
				if(jQuery(window).width()<600 && jQuery(window).height()<600 && is_touch_device()) {
						return false;
					}				
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


/* 
 * Used on the dash tile creation page for displaying a selector for the different styles of tile.
 * Styles are config controlled.
 */
function tileStyle($tile_type)
	{
	global $lang,$tile_styles,$promoted_resource,$resource_count;
	?>
	<div class="Question">
		<label for="tltype" class="stdwidth"><?php echo $lang["dashtilestyle"];?></label> 
		<table>
			<tbody>
				<tr>
					<?php
					$check=true;
					foreach($tile_styles[$tile_type] as $style)
						{?>
						<td width="10" valign="middle" >
							<input type="radio" class="tlstyle" id="tile_style_<?php echo $style;?>" name="tlstyle" value="<?php echo $style;?>" <?php echo $check? "checked":"";?>/>
						</td>
						<td align="left" valign="middle" >
							<label class="customFieldLabel" for="tile_style_<?php echo $style;?>"><?php echo $lang["tile_".$style];?></label>
						</td>
						<?php
						$check=false;
						}?>
				</tr>
			</tbody>
		</table>
		<div class="clearerleft"> </div>
	</div>
	<?php
	}
