<?php
#
# youtube_publish setup page
#

include '../../../include/db.php';
include '../../../include/authenticate.php'; if (!checkperm('a')) {exit ($lang['error-permissiondenied']);}
include '../../../include/general.php';

global $baseurl;
// Specify the name of this plugin and the heading to display for the page.
$plugin_name = 'youtube_publish';
$plugin_page_heading = $lang['youtube_publish_configuration'];

// Build the $page_def array of descriptions of each configuration variable the plugin uses.

$page_def[] = config_add_section_header($lang['youtube_publish_oauth2_advice']);


$page_def[] = config_add_section_header($lang['youtube_publish_authentication']);
$page_def[] = config_add_text_input('youtube_publish_client_id',$lang['youtube_publish_oauth2_clientid']);
$page_def[] = config_add_text_input('youtube_publish_client_secret',$lang['youtube_publish_oauth2_clientsecret']);
$page_def[] = config_add_text_input('youtube_publish_developer_key',$lang['youtube_publish_developer_key']);

$page_def[] = config_add_section_header($lang['youtube_publish_mappings_title']);
$page_def[] = config_add_single_ftype_select('youtube_publish_title_field',$lang["youtube_publish_title_field"]);
$page_def[] = config_add_single_ftype_select('youtube_publish_descriptionfield',$lang["youtube_publish_descriptionfield"]);
$page_def[] = config_add_multi_ftype_select('youtube_publish_keywords_fields',$lang["youtube_publish_keywords_fields"]);
$page_def[] = config_add_single_ftype_select('youtube_publish_url_field',$lang["youtube_publish_url_field"]);

$page_def[] = config_add_boolean_select('youtube_publish_allow_multiple',$lang['youtube_publish_allow_multiple']);

$page_def[] = config_add_multi_rtype_select('youtube_publish_restypes', $lang['youtube_publish_resource_types_to_include']);

// Do the page generation ritual -- don't change this section.
$upload_status = config_gen_setup_post($page_def, $plugin_name);
include '../../../include/header.php';
echo $lang["youtube_publish_callback_url"] . ": " . $baseurl . "/plugins/youtube_publish/pages/youtube_upload.php";
config_gen_setup_html($page_def, $plugin_name, $upload_status, $plugin_page_heading);
include '../../../include/footer.php';
