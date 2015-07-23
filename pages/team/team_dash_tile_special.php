<?php
include "../../include/db.php";
include "../../include/general.php";
include "../../include/authenticate.php";
if(!((checkperm("h") && !checkperm("hdta")) || (checkperm("dta") && !checkperm("h")))){exit($lang["error-permissiondenied"]);}
include "../../include/dash_functions.php";

include "../../include/header.php";
?>
<div class="BasicsBox"> 
<h2><?php echo $lang["specialdashtiles"];?></h2>
<p></p>
<ul>
	<li>
		<a href="<?php echo $baseurl."/pages/dash_tile.php?create=true&tltype=ftxt&modifylink=true&freetext=Helpful%20tips%20here&nostyleoptions=true&all_users=1&link=http://resourcespace.org/knowledge-base/&title=Knowledge%20Base";?>">
			<?php echo $lang["createdashtilefreetext"];?>
		</a>
	</li>
	<li>
		<a href="<?php echo $baseurl."/pages/dash_tile.php?create=true&submitdashtile=true&tltype=conf&tlstyle=pend&freetext=userpendingsubmission&all_users=true&link=/pages/search.php?search=%26archive=-2";?>">
			<?php echo $lang["createdashtilependingsubmission"];?>
		</a>
	</li>
	<li>
		<a href="<?php echo $baseurl."/pages/dash_tile.php?create=true&submitdashtile=true&tltype=conf&tlstyle=pend&freetext=userpending&all_users=true&link=/pages/search.php?search=%26archive=-1";?>">
			<?php echo $lang["createdashtilependingreview"];?>
		</a>
	</li>
</ul>

</div>
<?php
include "../../include/footer.php";
?>
