<?php
/*
 * User Dash - Tile Interface
 * Page for building tiles for the homepage dash interface
 *
 */

include "../include/db.php";
$k=getvalescaped("k","");
include "../include/authenticate.php";
include "../include/general.php";
include "../include/dash_functions.php";

if(checkperm("dtu") && !((checkperm("h") && !checkperm("hdta")) || (checkperm("dta") && !checkperm("h")))){exit($lang["error-permissiondenied"]);}
global $baseurl,$userref;
$error=false;
/* 
 * Process Submitted Tile 
 */
$submitdashtile=getvalescaped("submitdashtile",FALSE);
if($submitdashtile)
	{
	$buildurl="pages/ajax/dash_tile.php?tltype=".getvalescaped("tltype","")."&tlstyle=".getvalescaped("tlstyle","");
	$title=getvalescaped("title","");
	if((checkperm("h") && !checkperm("hdta")) || (checkperm("dta") && !checkperm("h")))
		{
		$all_users= (getvalescaped("all_users","false")=="true")? TRUE: FALSE;
		}
	else
		{
		$all_users=FALSE;
		}
	$default_order_by=getvalescaped("default_order_by","UNSET");
	$reload_interval=getvalescaped("reload_interval_secs","");
	$link=str_replace("&amp;","&",getvalescaped("link",""));
	if(strpos($link,$baseurl_short)===0) {
		$length = strlen($baseurl_short);
		$link = substr_replace($link,"",0,$length);
	}
	$link= preg_replace("/^\//","",$link);
	$resource_count=getvalescaped("resource_count",FALSE);

	$tile = create_dash_tile($buildurl,$link,$title,$reload_interval,$all_users,$default_order_by,$resource_count);
	if(!$all_users)
		{
		$existing = add_user_dash_tile($userref,$tile,$default_order_by);
		if(isset($existing[0]))
			{
			$error=$lang["existingdashtilefound"];
			}
		}

	/* CREATION SUCCESSFUL */
	if(!$error)
		{
		redirect($baseurl);
		exit();
		}
	include "../include/header.php";
	?>
	<h2><?php echo $lang["createnewdashtile"];?></h2>
	<?php 
	if($error)
		{?>
		<p class="FormError" style="margin-left:5px;">
		<?php echo $error;?>
		</p>
		<?php
		}?>
	<a href="<?php echo $link;?>">&gt;&nbsp;<?php echo $lang["returntopreviouspage"];?></a>
	<?php
	include "../include/footer.php";
	exit();
	}

/* 
 * Create New Tile Form 
 */
$create=getvalescaped("create",FALSE);
$tile_type=getvalescaped("tltype","");
if($create)
	{
	if($tile_type=="srch")
		{
		$srch=getvalescaped("link","");
		$order_by=getvalescaped("order_by","");
		$sort=getvalescaped("sort","");
		$archive=getvalescaped("archive","");
		$daylimit=getvalescaped("daylimit","");
		$restypes=getvalescaped("restypes","");
		$link=$srch."&order_by=" . urlencode($order_by) . "&sort=" . urlencode($sort) . "&archive=" . urlencode($archive) . "&daylimit=" . urlencode($daylimit) . "&k=" . urlencode($k) . "&restypes=" . urlencode($restypes);
		$title=preg_replace("/^.*search=/", "", $srch); 
		if(substr($title,0,11)=="!collection")
			{
				include "../include/collections_functions.php";
				$col= get_collection(preg_replace("/^!collection/", "", $title));
				$title=$col["name"];

			}
		else if(substr($title,0,7)=="!recent")
			{$title=$lang["recent"];}
		else if(substr($title,0,5)=="!last")
			{
			$last = preg_replace("/^!last/", "", $title);
			$title= ($last!="") ? $lang["last"]." ".$last : $lang["recent"];
			}
		}
	else if ($tile_type=="admn") 
		{
		$link="";
		$title="";
		}
	else if ($tile_type=="rcnt") 
		{
		$link="";
		$title="";
		}

	include "../include/header.php";
	?>
	<h2><?php echo $lang["createnewdashtile"];?></h2>
	<form id="create_dash" name="create_dash">
		<input type="hidden" name="tltype" value="<?php echo htmlspecialchars($tile_type)?>" />
		<input type="hidden" name="link" value="<?php echo htmlspecialchars($link);?>" />
		<input type="hidden" name="submitdashtile" value="true"/>


		<div class="Question">
			<label for="title" class="stdwidth"><?php echo $lang["dashtiletitle"];?></label> 
			<input type="text" name="title" value="<?php echo htmlspecialchars(ucfirst ($title));?>"/>
			<div class="clearerleft"></div>
		</div>
		<?php
		if((checkperm("h") && !checkperm("hdta")) || (checkperm("dta") && !checkperm("h")))
		{ ?>
		<div class="Question">
			<label for="all_users" class="stdwidth"><?php echo $lang["pushtoallusers"];?></label> 
			<table>
				<tbody>
					<tr>
						<td width="10" valign="middle" >
							<input type="radio" id="all_users_false" name="all_users" value="false" checked/>
						</td>
						<td align="left" valign="middle" >
							<label class="customFieldLabel" for="all_users_false"><?php echo $lang["no"];?></label>
						</td>
						<td width="10" valign="middle" >
							<input type="radio" id="all_users_true" name="all_users" value="true" />
						</td>
						<td align="left" valign="middle" >
							<label class="customFieldLabel" for="all_users_true"><?php echo $lang["yes"];?></label>
						</td>
					</tr>
				</tbody>
			</table>
			<div class="clearerleft"> </div>
		</div>
		<?php 
		}

		tileStyle($tile_type);?>

		<div class="Question">
			 <div class="Inline"><input name="Submit" type="submit" value="&nbsp;&nbsp;<?php echo $lang["create"]?>&nbsp;&nbsp;" /></div>
			<div class="clearerleft"> </div>
		</div>
	</form>
	<?php
	include "../include/footer.php";
	}

function tileStyle($tile_type)
	{
	global $lang;
	$tile_styles["srch"]=["thmbs","multi"];
	$tile_styles["admn"]=["stat"];
	$tile_styles["rcnt"]=["stat"];
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
							<label class="customFieldLabel" for="tile_style_<?php echo $style;?>"><?php echo $style;?></label>
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
	if($tile_type=="srch")
		{?>
		<div class="Question" id="showresourcecount" >
			<label for="tltype" class="stdwidth"><?php echo $lang["showresourcecount"];?></label> 
			<table>
				<tbody>
					<tr>
						<td width="10" valign="middle" >
							<input type="checkbox" id="resource_count" name="resource_count" value="1" />
						</td>
					</tr>
				</tbody>
			</table>
			<div class="clearerleft"> </div>
		</div>
		<script>
			jQuery(".tlstyle").change(function(){
				checked=jQuery(".tlstyle:checked").val();
				if(checked=="thmbs" || checked=="multi") {
					jQuery("#showresourcecount").show();
				}
				else {
					jQuery("#showresourcecount").hide();
				}
			});
		</script>
	<?php
		}
	}