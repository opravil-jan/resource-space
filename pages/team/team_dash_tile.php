<?php
include "../../include/db.php";
include "../../include/general.php";
include "../../include/authenticate.php";
if(!((checkperm("h") && !checkperm("hdta")) || (checkperm("dta") && !checkperm("h")))){exit($lang["error-permissiondenied"]);}
include "../../include/dash_functions.php";

include "../../include/header.php";
?>
<div class="BasicsBox"> 

<p>
	<a href="<?php echo $baseurl_short?>pages/team/team_dash_admin.php" onClick="return CentralSpaceLoad(this,true);">
		&gt;&nbsp;<?php echo $lang["dasheditmodifytiles"];?>
	</a>
</p>
<p>
	<a href="<?php echo $baseurl."/pages/dash_tile.php?create=true&tltype=ftxt&modifylink=true&freetext=Helpful%20tips%20here&nostyleoptions=true&all_users=1&link=http://resourcespace.org/knowledge-base/&title=Knowledge%20Base";?>">&gt;&nbsp;<?php echo $lang["createdashtilefreetext"]?></a>
</p>
	<div id="HomePanelContainer" class="manage-all-user-tiles">
	<?php
	get_default_dash();
	?>
	</div>
</div>
<?php
include "../../include/footer.php";
?>
