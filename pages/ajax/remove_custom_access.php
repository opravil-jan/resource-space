<?php
include dirname(__FILE__) . '/../../include/db.php';
include dirname(__FILE__) . '/../../include/authenticate.php';
include dirname(__FILE__) . '/../../include/general.php';

$resource = getvalescaped('resource', '');
$user_ref = getvalescaped('user_ref', '');

// Delete the record from the database
$query = sprintf('
		DELETE FROM resource_custom_access 
		      WHERE resource = "%s"
		        AND user = "%s";
	',
	$resource,
	$user_ref
);
sql_query($query);