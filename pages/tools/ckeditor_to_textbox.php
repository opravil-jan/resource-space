<?php

# This experimental script is useful if you've switched a field from CKEditor to textbox and will remove all the html saved in the db.
# Please set $encoding per the information at http://php.net/manual/en/function.html-entity-decode.php

include "../../include/db.php";
include "../../include/authenticate.php"; if (!checkperm("a")) {exit("Permission denied");}
include "../../include/general.php";
include "../../include/resource_functions.php";

$encoding='';

# ex. pages/tools/ckeditor_to_textbox.php?fieldrefs=75,3&refs=5342
$fieldrefs=getval("fieldrefs",0);
$refs=getval("refs",0);

if ($fieldrefs==0){
	die ("Please add a list of refs to the fieldrefs url parameter, which are the ref numbers of the fields that you would like to convert. <br /><br />For example: pages/tools/ckeditor_to_textbox.php?fieldrefs=75,3");
}
else {
	$fields=explode(",",$fieldrefs);
	foreach ($fields as $field){
		$field_data=sql_query("select * from resource_type_field where ref=$field");
		if(!empty($field_data)){
			$field_data=$field_data[0];
		}
		# check to see if the field is set for textbox
		if($field_data['type']!=0 && $field_data['type']!=1 && $field_data['type']!=5){
			echo "Field ".$field_data['title']." isn't set to a textbox type! Skipping...<br/>";
		}
		else{
			echo "Updaing values for field ".$field_data['title']."<br/>";
			# get resource data
			$sql="select * from resource_data where resource_type_field=$field";
			if($refs!=0){
				$refs=explode(", ",$refs);
				$rsids='';
				foreach($refs as $ref){
					if($rsids==''){$rsids="(".$ref;}
					else{$rsids.=",$ref";}
				}
				$rsids.=")";
				$sql.=" and resource in $rsids";
			}
			echo "<br/>";
			$edit_resources=sql_query($sql);
			foreach($edit_resources as $edit_resource){
				echo "Modifying resource ".$edit_resource['resource']."...";
				# remove html tags
				$new_value=strip_tags($edit_resource['value']);
				if($encoding!=''){
					$new_value=html_entity_decode($new_value,'',$encoding);
				}
				else{
					$new_value=html_entity_decode($new_value);
				}
				$wait=update_field($edit_resource['resource'],$edit_resource['resource_type_field'],$new_value);
				echo "Done!<br/>";				
			}
			echo "Finished updating values for field ".$field_data['title']."<br/>";
		}
	}
	echo "Finished updating all fields.";
}
			
