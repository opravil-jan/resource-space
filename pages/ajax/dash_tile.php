<?php
/*
 * User Tile Ajax Interface
 * Ajax functions for the homepage dash interface
 *
 */

include "../../include/db.php";
include "../../include/authenticate.php";
include "../../include/general.php";
include "../../include/search_functions.php";
include "../../include/collections_functions.php";
include "../../include/dash_functions.php";


global $userref,$baseurl_short;
/* Tile */
$rawtile=getvalescaped("tile",null);
if(isset($rawtile) && !empty($rawtile))
	{
	if(!is_numeric($rawtile)){exit($lang["invaliddashtile"]);}
	$tile=get_tile($rawtile);
	if(!$tile){exit($lang["nodashtilefound"]);}
	}

/* User Tile */
$user_rawtile=getvalescaped("user_tile",null);
if(isset($user_rawtile) && !empty($user_rawtile))
	{
	if(!is_numeric($user_rawtile)){exit($lang["invaliddashtile"]);}
	$usertile=get_user_tile($user_rawtile,$userref);
	if(!$usertile){exit($lang["nodashtilefound"]);}
	}

/* 
 * Reorder Tile
 */
	$index=getvalescaped("new_index","");
	if(!empty($index) && is_numeric($index) && isset($usertile))
		{
		update_user_dash_tile_order($userref,$usertile["ref"],$index);
		reorder_user_dash($userref);
		echo "Tile ".$usertile["ref"]." at index: ".($index+5);
		exit();
		}
	if(!empty($index) && is_numeric($index) && isset($tile) && !isset($usertile))
		{
		update_default_dash_tile_order($tile["ref"],$index);
		reorder_default_dash();
		echo "Tile ".$tile["ref"]." at index: ".($index+5);
		exit();
		}

/* 
 * Delete Tile 
 */
	$delete=getvalescaped("delete",false);
	if($delete && isset($usertile))
		{
		if(checkperm("dtu") && !((checkperm("h") && !checkperm("hdta")) || (checkperm("dta") && !checkperm("h")))){exit($lang["error-permissiondenied"]);}
		delete_user_dash_tile($usertile["ref"],$userref);
		reorder_user_dash($userref);
		echo "Deleted ".$usertile['ref'];
		exit();
		}
	if($delete && isset($tile) && !isset($usertile))
		{
		if(!((checkperm("h") && !checkperm("hdta")) || (checkperm("dta") && !checkperm("h")))){exit($lang["error-permissiondenied"]);}
		delete_dash_tile($tile["ref"]);
		reorder_default_dash();
		echo "Deleted ".$tile['ref'];
		exit();
		}



/* 
 * Generating Tiles 
 */
$tile_type=getvalescaped("tltype","");
$tile_style=getvalescaped("tlstyle","");
$tile_id= (isset($usertile)) ? "contents_user_tile".$usertile["ref"] : "contents_tile".$tile["ref"];
$tile_width = getvalescaped("tlwidth","");
$tile_height = getvalescaped("tlheight","");
if(!is_numeric($tile_width) || !is_numeric($tile_height)){exit($lang["error-missingtileheightorwidth"]);}

	/*
	 * Search Type Layouts
	 */
	if($tile_type=="srch" && $tile_style=="thmbs")
		{
		$search_string = explode('?',$tile["link"]);
		parse_str(str_replace("&amp;","&",$search_string[1]),$search_string);
		$count = ($tile["resource_count"]) ? "-1" : "1";
		$tile_search=do_search($search_string["search"],$search_string["restypes"],$search_string["order_by"],$search_string["archive"],$count,$search_string["sort"],$access_override=false,$starsearch=0,$ignore_filters=false,$return_disk_usage=false,$recent_search_daylimit="", $go=false);
		
		$found_resources=true;
		$count=count($tile_search);
		if(!isset($tile_search[0]["ref"]))
			{
			$found_resources=false;
			$count=0;
			}

		if($found_resources)
			{
			$defaultpreview=false;
			$previewpath=get_resource_path($tile_search[0]["ref"],true,"pre",false, "jpg", -1, 1, false);
			if (file_exists($previewpath))
				{
                $previewpath=get_resource_path($tile_search[0]["ref"],false,"pre",false, "jpg", -1, 1, false);
            	}
            else 
            	{
                $previewpath=$baseurl_short."gfx/".get_nopreview_icon($tile_search[0]["resource_type"],$tile_search[0]["file_extension"],false);
                $defaultpreview=true;
            	}
			?>
			<img 
				src="<?php echo $previewpath ?>" 
				<?php 
				if($defaultpreview)
					{
					?>
					style="position:absolute;top:<?php echo ($tile_height-128)/2 ?>px;left:<?php echo ($tile_width-128)/2 ?>px;"
					<?php
					}
				else 
					{
					#fit image to tile size
					if(($tile_search[0]["thumb_width"]*0.7)>=$tile_search[0]["thumb_height"])
						{
						$ratio = $tile_search[0]["thumb_height"] / $tile_height;
						$width = $tile_search[0]["thumb_width"] / $ratio;
						if($width<$tile_width){echo "width='100%' ";}
						else {echo "height='100%' ";}
						}
					else
						{
						$ratio = $tile_search[0]["thumb_width"] / $tile_width;
						$height = $tile_search[0]["thumb_height"] / $ratio;
						if($height<$tile_height){echo "height='100%' ";}
						else {echo "width='100%' ";}
						}
					?>
					style="position:absolute;top:0;left:0;"
					<?php
					}?>
				class="thmbs_tile_img"
			/>
			<?php
			}
		$icon = ""; 
		if(substr($search_string["search"],0,11)=="!collection")
			{$icon="collection";}
		else if(substr($search_string["search"],0,7)=="!recent" || substr($search_string["search"],0,5)=="!last")
			{$icon="clock";}
		else{$icon="search";}
		echo "<span class='".$icon."-icon'></span>";
		?>
		<h2 class="title thmbs_tile">
			<?php echo htmlspecialchars($tile["title"]);?>
		</h2>
		<?php 
		if(!$found_resources)
			{
			echo "<p class='no_resources'>".$lang["noresourcesfound"]."</p>";
			}
		if($tile["resource_count"])
			{?>
			<p class="tile_corner_box">
			<span class="count-icon"></span>
			<?php echo $count; ?>
			</p>
			<?php
			}
		?>
		<style>
			#<?php echo $tile_id;?> {
				  padding: 0;
				  overflow: hidden;
				  position: relative;
				  height: 100%;
				  width: 100%;
				  min-height: 180px;
			}
			#<?php echo $tile_id;?> h2.thmbs_tile {
				float: none;
				position: relative;
				padding-left: 60px;
				padding-right: 15px;
				padding-top: 18px;
				text-transform: capitalize;
			}
			#<?php echo $tile_id;?> p.no_resources {
					float: none;
					position: relative;
					padding-left: 15px;
					padding-right: 15px;
					padding-top: 5px;
				}
		</style>
		<?php
		exit;
		}

/* Multi Resource Search Tile */
	if($tile_type=="srch" && $tile_style=="multi")
		{
		$search_string = explode('?',$tile["link"]);
		parse_str(str_replace("&amp;","&",$search_string[1]),$search_string);
		$count = ($tile["resource_count"]) ? "-1" : "4";
		$resources=do_search($search_string["search"],$search_string["restypes"],$search_string["order_by"],$search_string["archive"],$count,$search_string["sort"],$access_override=false,$starsearch=0,$ignore_filters=false,$return_disk_usage=false,$recent_search_daylimit="", $go=false);
		$margin=3;
		$gap=20;
		$images=0;
		$img_size="pre";
		if(count($resources)<3){$margin=10;$gap=40;}
		for ($i=0;$i<count($resources) && $images<=3;$i++)
            {
			$ref=$resources[$i]['ref'];
            $previewpath=get_resource_path($ref, true, $img_size, false, "jpg", -1, 1, false);
            if (file_exists($previewpath)){
                $previewpath=get_resource_path($ref,false,$img_size,false,"jpg",-1,1,false,$resources[$i]["file_modified"]);
            }
            else {
                $previewpath=$baseurl_short."gfx/".get_nopreview_icon($resources[$i]["resource_type"],$resources[$i]["file_extension"],$img_size);$border=false;
            }
            $modifiedurl=hook('searchpublicmodifyurl');
			if($modifiedurl)
				{
				$previewpath=$modifiedurl;
				$border=true;
				}
            $images++;
            #$space=$margin+($images-1)*$gap;
            $space=($images-1)*42;
            ?>
            <img style="position: absolute; top:0;left:<?php echo ($space*1.5) ?>px;height:100%;" src="<?php echo $previewpath?>">
            <?php				
			}
		$icon = ""; 
		if(substr($search_string["search"],0,11)=="!collection")
			{$icon="collection";}
		else if(substr($search_string["search"],0,7)=="!recent" || substr($search_string["search"],0,5)=="!last")
			{$icon="clock";}
		else{$icon="search";}
		echo "<span class='".$icon."-icon'></span>";
		?>
		<h2 class="title multi_tile">
			<?php echo htmlspecialchars($tile["title"]); ?>
		</h2>
		<?php 
		if($tile["resource_count"])
			{?>
			<p class="tile_corner_box">
			<span class="count-icon"></span>
			<?php echo count($resources); ?>
			</p>
			<?php
			}?>
		<style>
				#<?php echo $tile_id;?> {
					  padding: 0;
					  overflow: hidden;
					  position: relative;
					  height: 100%;
					  width: 100%;
					  min-height: 180px;
				}
				#<?php echo $tile_id;?> h2 {
					float: none;
					position: relative;
					padding-left: 60px;
					padding-right: 15px;
					padding-top: 18px;
				}
		</style>
		<?php
		exit;
		}
		/* STAT TILE PUT ON HOLD 
	if($tile_type=="srch" && $tile_style=="stat")
		{
		$search_string = explode('?',$tile["link"]);
		parse_str(str_replace("&amp;","&",$search_string[1]),$search_string);
		$resources=do_search($search_string["search"],$search_string["restypes"],$search_string["order_by"],$search_string["archive"],-1,$search_string["sort"],$access_override=false,$starsearch=0,$ignore_filters=false,$return_disk_usage=false,$recent_search_daylimit="", $go=false);
		
		?>
		<h2 class="title stat_tile"><?php echo htmlspecialchars($tile["title"]);?></h2>
		<p><?php echo $lang["property-total_resources"] .": ".count($resources)?></p>
		<p>More information Here</p>
		<style>
				#contents_user_tile<?php echo $usertile["ref"];?> {
					  padding: 0;
					  overflow: hidden;
					  position: relative;
					  height: 100%;
					  width: 100%;
					  min-height: 180px;
				}
				#contents_user_tile<?php echo $usertile["ref"];?> h2 {
					float: none;
					position: relative;
					padding-left: 15px;
					padding-right: 15px;
					padding-top: 18px;
				}
				#contents_user_tile<?php echo $usertile["ref"];?> p {
					float: none;
					position: relative;
					padding-left: 15px;
					padding-right: 15px;
					padding-top: 5px;
				}
		</style>
		<?php
		exit;
		}
		*/


	/*
	 * Admin Type Layouts
	 */
	if($tile_type=="admn" && $tile_style=="stat")
		{?>
		<h2 class="title stat_tile"><?php echo htmlspecialchars($tile["title"]);?></h2>
		<p>I need some admin stats</p>
		<?php
		exit;
		}

	/*
	 * Recent Activity Type Layouts
	 */
	if($tile_type=="rcnt" && $tile_style=="stat")
		{?>
		<h2 class="title stat_tile"><?php echo htmlspecialchars($tile["title"]);?></h2>
		<p>I need some recent activity stats</p>
		<?php
		exit;
		}
