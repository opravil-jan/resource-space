<?php

include "../../include/db.php";
include "../../include/general.php";
include "../../include/authenticate.php";
include "../../include/resource_functions.php";
include "../../include/header.php";
?>


<div class="BasicsBox"> 
  <h1><?php echo $lang["myaccount"]?></h1>
  <p><?php echo text("introtext")?></p>
  
	<div class="VerticalNav">
	<ul>
	
        <li><a href="<?php echo $baseurl_short?>pages/user/user_change_password.php" onClick="return CentralSpaceLoad(this,true);"><?php echo $lang["changeyourpassword"]?></a></li>
    
        <li><a href="<?php echo $baseurl_short?>pages/contribute.php" onClick="return CentralSpaceLoad(this,true);"><?php echo $lang["mycontributions"]?></a></li>

	<li><a href="<?php echo $baseurl_short?>pages/collection_manage.php" onClick="return CentralSpaceLoad(this,true);"><?php echo $lang["mycollections"]?></a></li>
		
	</ul>
	</div>

</div>

<?php
include "../../include/footer.php";
?>
