<?php 
function HookVideo_spliceAllRender_actions_add_collection_option(){
	global $collection,$count_result,$lang,$pagename,$baseurl_short;
	
	$options = '';
	if ($pagename=="collections" && $count_result!=0)
		{
		$data_attribute['url'] = sprintf('%splugins/video_splice/pages/splice.php?collection=%s',
            $baseurl_short,
            urlencode($collection)
        );
        $options.=render_dropdown_option('video_splice',$lang["action-splice"],$data_attribute);
	}
	return $options;
}
