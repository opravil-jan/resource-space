<?php

include "../../include/db.php";
include "../../include/general.php";
include "../../include/authenticate.php";
include "../../include/header.php";

$sortby = getval("sortby","");
$filter = getval("filter","");
$backurl=getval("backurl","");

?><script>

var sortByactivitylog = "<?php echo $sortby; ?>";
var filteractivitylog = "<?php echo $filter; ?>";

function SystemConsoleactivitylogLoad(refresh_secs, extra)
{
	if (extra == undefined)
	{
		extra = "";
	}
	CentralSpaceLoad("team_system_log.php?sortby=" + encodeURIComponent(sortByactivitylog) + '&filter=' + encodeURIComponent(filteractivitylog) + extra);
}

</script>

<div class="BasicsBox">

	<?php if ($backurl!=""){?><p><a href="<?php echo $backurl?>" onClick="return CentralSpaceLoad(this,true);">&lt;&nbsp;<?php echo $lang["manageusers"]?></a></p><?php } ?>

	<h1><?php echo $lang["systemlog"]; ?></h1>
	<p><?php echo text("introtext"); ?></p>
</div>

<input type="text" class="stdwidth" placeholder="Filter" value="<?php echo $filter; ?>" onkeyup="if(this.value=='')
	   {
	   jQuery('#filterbuttonactivitylog').attr('disabled','disabled');
	   jQuery('#clearbuttonactivitylog').attr('disabled','disabled')
	   } else {
	   jQuery('#filterbuttonactivitylog').removeAttr('disabled');
	   jQuery('#clearbuttonactivitylog').removeAttr('disabled')
	   }
	   filteractivitylog=this.value;
	   var e = event;
	   if (e.keyCode === 13)
	   {
	   SystemConsoleactivitylogLoad();
	   }">
</input>
<input id="filterbuttonactivitylog" type="button" onclick="SystemConsoleactivitylogLoad();" value="Filter">
<input id="clearbuttonactivitylog" type="button" onclick="filteractivitylog=''; SystemConsoleactivitylogLoad();" value="Clear">

<?php

$_GET['callback']="activitylog";
include_once __DIR__ . "/team_system_console.php";

include "../../include/footer.php";
