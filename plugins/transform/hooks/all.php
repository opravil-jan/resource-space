<?php 

function HookTransformAllAdditionalheaderjs(){
global $baseurl,$baseurl_short;?>
<link rel="stylesheet" href="<?php echo $baseurl_short?>plugins/transform/lib/jcrop/css/jquery.Jcrop.min.css" type="text/css" />
<script type="text/javascript" src="<?php echo $baseurl?>/plugins/transform/lib/jcrop/js/jquery.Jcrop.min.js" language="javascript"></script>
<?php } 
function HookTransformAllRender_actions_add_collection_option(){
	global $cropper_enable_batch,$count_result,$cinfo,$lang,$collection,$baseurl_short;
	
	$options = '';
	if ($cropper_enable_batch && $count_result>0 && $cinfo["allow_changes"]==1){
		$data_attribute['url'] = sprintf('%splugins/transform/pages/collection_transform.php?collection=%s',
            $baseurl_short,
            urlencode($collection)
        );
        $options.=render_dropdown_option('transform', $lang["transform"],$data_attribute);
	}
	
	return $options;
}
function HookTransformAllAdditional_title_pages_array(){
        return array("crop","collection_transform");
}
function HookTransformAllAdditional_title_pages(){
        global $pagename,$lang,$applicationname;
        switch($pagename){
			case "crop":
				global $original;
				if($original){
					$pagetitle=$lang['transform_original'];
				}
				else{
					$pagetitle=$lang['transformimage'];
                }
                break;
            case "collection_transform":
				$pagetitle=$lang['batchtransform'];
				break;
		}
        if(isset($pagetitle)){
                echo "<script language='javascript'>\n";
                echo "document.title = \"$applicationname - $pagetitle\";\n";
                echo "</script>";
        }
}
