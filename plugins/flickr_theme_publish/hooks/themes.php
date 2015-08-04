<?php

function HookFlickr_theme_publishThemesAddcustomtool($theme)
	{
	# Adds a Flickr link to the themes page.
	global $baseurl, $lang;
	
	# Work out how many resources in this theme are unpublished.
	$unpublished=sql_value("select count(*) value from resource join collection_resource on resource.ref=collection_resource.resource where collection_resource.collection='" . $theme . "' and flickr_photo_id is null",0);

	?>
	&nbsp;<a onClick="return CentralSpaceLoad(this,true);" href="<?php echo $baseurl?>/plugins/flickr_theme_publish/pages/sync.php?theme=<?php echo $theme?>">&gt;&nbsp;Flickr<?php if ($unpublished>0) { echo " <strong>(" . ($unpublished==1 ? $lang["unpublished-1"] : str_replace("%number", $unpublished, $lang["unpublished-2"])) . ")</strong>"; } ?></a>
	<?php
	}

function HookFlickr_theme_publishThemesRender_actions_add_collection_option(){
	global $getthemes, $m, $lang, $baseurl_short;
    $theme = $getthemes[$m];
	$options = '';
	// these aren't really set on themes.php
	$result=get_collection_resources($theme['ref']);
	$count_result=count($result);
	
	if ($count_result>0) # Don't show the option if the theme is empty.
        {
			$lang_string=$lang["publish_to_flickr"];
			$unpublished = sql_value("select count(*) value from resource join collection_resource on resource.ref=collection_resource.resource where collection_resource.collection='" . $theme["ref"] . "' and flickr_photo_id is null",0);
			if ($unpublished>0) {
				$lang_string.=" <strong>(" . ($unpublished==1 ? $lang["unpublished-1"] : str_replace("%number", $unpublished, $lang["unpublished-2"])) . ")</strong>";
			}
		$data_attribute['url'] = sprintf('%splugins/flickr_theme_publish/pages/sync.php?theme=%s',
            $baseurl_short,
            urlencode($theme["ref"])
        );
        $options.=render_dropdown_option('flickr_publish', $lang_string,$data_attribute);
	}
	return $options;
}
?>
