<?php

include "../../include/db.php";
include "../../include/general.php";
include "../../include/authenticate.php";
include "../../include/dash_functions.php";

#If can't manage own dash return to user home.
if(!($home_dash && checkPermission_dashmanage()))
	{header("location: ".$baseurl_short."pages/user/user_home.php");exit;}


#Building function
function build_user_dash_tile_list($user)
	{
	global $lang,$baseurl_short;
	#All available Tiles
	$dtiles_available = get_user_available_tiles($user);
	foreach($dtiles_available as $tile)
  		{
  		$checked = false;
  		if(!empty($tile["dash_tile"]))
  			{$checked=true;}
  		?>
  		<tr>
  			<td>
  				<input 
  					type="checkbox" 
  					class="tilecheck" 
  					name="usertiles[]" 
  					value="<?php echo $tile["ref"];?>" 
  					onChange="changeTile(<?php echo $tile["ref"];?>,<?php echo $tile["all_users"];?>);"
  					<?php echo $checked?"checked":"";?> 
  				/>
  			</td>
  			<td><?php echo $tile["title"];?></td>
  			<td>
  				<?php 
  				if(strlen($tile["txt"])>75)
  					{
  					echo substr($tile["txt"],0,72)."...";
  					}
  				else
  					{
  					echo $tile["txt"];
  					}
  				?>
  			</td>
  			<td>
  				<?php 
  				if(strlen($tile["link"])>75)
  					{
  					echo substr($tile["link"],0,72)."...";
  					}
  				else
  					{
  					echo $tile["link"];
  					}
  				?>
  			</td>
  			<td><?php echo $tile["resource_count"]? $lang["yes"]: $lang["no"];?></td>
  			<td>
  				<?php
  				if  (	
  						$tile["allow_delete"]
  						&&
  						(
  							($tile["all_users"] && checkPermission_dashadmin()) 
  							|| 
  							(!$tile["all_users"] && (checkPermission_dashuser() || checkPermission_dashadmin()))
	  					)
  					)
  					{ ?>
  					<a href="<?php echo $baseurl_short; ?>pages/dash_tile.php?edit=<?php echo $tile['ref'];?>" ><?php echo $lang["action-edit"];?></a>
  					<?php
  					}
  				?>
  			</td>
  		</tr>
  		<?php
  		}
  	}

if(getvalescaped("quicksave",FALSE))
	{
	$tile = getvalescaped("tile","");
	#If a valid tile value supplied
	if(!empty($tile) && is_numeric($tile))
		{
		#Tile available to this user?
		$available = get_user_available_tiles($userref,$tile);
		if(!empty($available))
			{
			$tile = $available[0]["tile"];
			$usertile = $available[0]["usertile"];
			if(get_user_tile($usertile,$userref))
				{
				#Delete if the user already has the tile
				delete_user_dash_tile($usertile,$userref);
				exit(build_user_dash_tile_list($userref));
				}
			else
				{
				#Add to the front of the pile if the user already has the tile
				add_user_dash_tile($userref,$tile,5);
				exit(build_user_dash_tile_list($userref));
				}
			}
		}
	exit("Save Failed");
	}

if(getvalescaped("submit",FALSE))
	{
	$tiles = getvalescaped("usertiles","");
	if(empty($tiles))
		{
		empty_user_dash($userref);
		}
	else
		{
		#Start Fresh
		empty_user_dash($userref,false);
		$order_by = 10;
		foreach($tiles as $tile)
			{
			add_user_dash_tile($userref,$tile,$order_by);
			$order_by+=10;
			}
		}
	}


include "../../include/header.php";
?>
<div class="BasicsBox"> 
	<h1><?php echo $lang["manage_own_dash"];?></h1>
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
	  	build_user_dash_tile_list($userref);
	  	?>
	  </tbody>
  	</table>
  	<div id="confirm_dialog" style="display:none;text-align:left;"><?php echo $lang["dashtiledeleteusertile"];?></div>
  	<noscript>
	  	<div class="QuestionSubmit">
	  		<input type="submit" value="<?php echo $lang["save"]?>"/>
	  	</div>
  	</noscript>
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
	<div>
		<?php
		# Create New Tile (Has dtu or dta (hdta) permissions)
		if($home_dash && checkPermission_dashmanage())
			{ ?>
			<p>
				<a href="<?php echo $baseurl."/pages/dash_tile.php?create=true&tltype=ftxt&modifylink=true&freetext=Helpful%20tips%20here&nostyleoptions=true&all_users=1&link=http://resourcespace.org/knowledge-base/&title=Knowledge%20Base";?>">&gt;&nbsp; <?php echo $lang["createdashtilefreetext"]?></a>
			</p>
			<?php
			} ?>
	</div>
</div>

<?php
include "../../include/footer.php";