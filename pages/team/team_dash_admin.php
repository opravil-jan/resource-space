<?php
include "../../include/db.php";
include "../../include/general.php";
include "../../include/authenticate.php";
if(!checkPermission_dashadmin()){exit($lang["error-permissiondenied"]);}
include "../../include/dash_functions.php";



if(getvalescaped("quicksave",FALSE))
	{
	$tile = getvalescaped("tile","");
	#If a valid tile value supplied
	if(!empty($tile) && is_numeric($tile))
		{
		#Tile available to this user?
		$available = get_alluser_available_tiles($tile);
		if(!empty($available))
			{
			$tile = $available[0];
			$active = all_user_dash_tile_active($tile["ref"]);

			if($active)
				{
				#Delete if the tile is active		
				#Check config tiles for permanent deletion
				$force = false;
				$search_string = explode('?',$tile["url"]);
				parse_str(str_replace("&amp;","&",$search_string[1]),$search_string);
				if($search_string["tltype"]=="conf")
					{$force = !checkTileConfig($tile,$search_string["tlstyle"]);}

				delete_dash_tile($tile["ref"],true,$force);
				reorder_default_dash();
				$dtiles_available = get_alluser_available_tiles();
				exit(build_dash_tile_list($dtiles_available));
				}
			else
				{
				#Add to the front of the pile if the user already has the tile
				sql_query("DELETE FROM user_dash_tile WHERE dash_tile=".$tile["ref"]);
				sql_query("INSERT user_dash_tile (user,dash_tile,order_by) SELECT user.ref,'".$tile["ref"]."',5 FROM user");

				$dtiles_available = get_alluser_available_tiles();
				exit(build_dash_tile_list($dtiles_available));
				}
			}
		}
	exit("Save Failed");
	}

include "../../include/header.php";
?>
<div class="BasicsBox"> 
	<h1><?php echo $lang["managedefaultdash"];?></h1>
<p>
	<a href="<?php echo $baseurl_short?>pages/team/team_home.php" onClick="return CentralSpaceLoad(this,true);">
		&lt;&nbsp;<?php echo $lang["backtoteamhome"]?>
	</a>
</p>
<p>
	<a href="<?php echo $baseurl_short?>pages/team/team_dash_tile.php" onClick="return CentralSpaceLoad(this,true);">
		&lt;&nbsp;<?php echo $lang["managedefaultdash"]?>
	</a>
</p>
<p>
	<a href="<?php echo $baseurl."/pages/dash_tile.php?create=true&tltype=ftxt&modifylink=true&freetext=Helpful%20tips%20here&nostyleoptions=true&all_users=1&link=http://resourcespace.org/knowledge-base/&title=Knowledge%20Base";?>"
	>
		&gt;&nbsp;<?php echo $lang["createdashtilefreetext"]?>
	</a>
</p>
	<form class="Listview">
	<input type="hidden" name="submit" value="true" />
	<table class="ListviewStyle">
		<thead>
			<tr class="ListviewTitleStyle">
				<td><?php echo $lang["dashtileshow"];?></td>
				<td><?php echo $lang["dashtiletitle"];?></td>
				<td><?php echo $lang["dashtiletext"];?></td>
				<td><?php echo $lang["dashtilelink"];?></td>
				<td><?php echo $lang["showresourcecount"];?></td>
				<td><?php echo $lang["tools"];?></td>
			</tr>
		</thead>
		<tbody id="dashtilelist">
	  	<?php
	  	$dtiles_available = get_alluser_available_tiles();
		build_dash_tile_list($dtiles_available);
	  	?>
	  </tbody>
  	</table>
  	<div id="confirm_dialog" style="display:none;text-align:left;"><?php echo $lang["dashtiledeleteusertile"];?></div>
	</form>
	<script type="text/javascript">
		function processTileChange(tile) {
			jQuery.post(
				window.location,
				{"tile":tile,"quicksave":"true"},
				function(data){
					jQuery("#dashtilelist").html(data);
				}
			);
		}
		function changeTile(tile,all_users) {
			if(all_users==0) {
				jQuery("#confirm_dialog").dialog({
		        	title:'<?php echo $lang["dashtiledelete"]; ?>',
		        	modal: true,
    				resizable: false,
					dialogClass: 'confirm-dialog no-close',
                    buttons: {
                        "<?php echo $lang['confirmdashtiledelete'] ?>": function() {processTileChange(tile); jQuery(this).dialog( "close" );},
                        "<?php echo $lang['cancel'] ?>":  function() { jQuery(".tilecheck[value="+tile+"]").attr('checked', true); jQuery(this).dialog('close'); }
                    }
                });
			} else {
				processTileChange(tile);
			}
		}
	</script>
</div>
<?php
include "../../include/footer.php";
?>
