<?php
#
# Api_search setup page
#

// Do the include and authorization checking ritual -- don't change this section.
include '../../../include/db.php';
include '../../../include/authenticate.php'; if (!checkperm('a')) {exit ($lang['error-permissiondenied']);}
include '../../../include/general.php';
include '../../../include/search_functions.php';

// Specify the name of this plugin and the heading to display for the page.
$plugin_name = 'api_search';
$plugin_page_heading = $lang['api_search_configuration'];

// Build the $page_def array of descriptions of each configuration variable the plugin uses.
$result=do_search('','','relevance',0,1);
if (isset($result[0])){
	$list="";
	foreach ($result[0] as $key=>$value){
		$list.=$key.", ";
	}
}
$list= rtrim(trim($list),",");

$page_def[] = config_add_text_input('api_search_exclude_fields', $lang['api_search_exclude_fields']);
$page_def[]=config_add_html($lang['api_search_excludable_fields'].": $list");


// Do the page generation ritual -- don't change this section.
$upload_status = config_gen_setup_post($page_def, $plugin_name);
include '../../../include/header.php';
config_gen_setup_html($page_def, $plugin_name, $upload_status, $plugin_page_heading);
include '../../../include/footer.php';
