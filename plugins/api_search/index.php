<?php

include(dirname(__FILE__)."/../../include/db.php");
include(dirname(__FILE__)."/../../include/general.php");
include(dirname(__FILE__)."/../../include/search_functions.php");
include(dirname(__FILE__)."/../../include/resource_functions.php");
$api=true;

include(dirname(__FILE__)."/../../include/authenticate.php");

// required: check that this plugin is available to the user
if (!in_array("api_search",$plugins)){die("no access");}

$search=getval("search","");
$search=refine_searchstring($search);
$restypes=getvalescaped("restypes","");
$order_by=getvalescaped("order_by","relevance");
$sort=getvalescaped("sort","desc");
$archive=getvalescaped("archive",0);
$starsearch=getvalescaped("starsearch","");
$collection=getvalescaped("collection","",true);

$help=getval("help","");
if ($help!=""){
header('Content-type: text/plain');
echo file_get_contents("readme.txt");
die();
}


if ($api_search['signed']){

// test signature? get query string minus leading ? and skey parameter
$test_query="";
parse_str($_SERVER["QUERY_STRING"],$parsed);
foreach ($parsed as $parsed_parameter=>$value){
    if ($parsed_parameter!="skey"){
        $test_query.=$parsed_parameter.'='.$value."&";
    }
    }
$test_query=rtrim($test_query,"&");

    // get hashkey that should have been used to create a signature.
    $hashkey=md5($api_scramble_key.getval("key",""));

    // generate the signature required to match against given skey to continue
    $keytotest = md5($hashkey.$test_query);

    if ($keytotest <> getval('skey','')){
		header("HTTP/1.0 403 Forbidden.");
		echo "HTTP/1.0 403 Forbidden. Invalid Signature";
		exit;
	}
}

if ($collection!=""){$searchadd="!collection".$collection.", ";} else {$searchadd="";}

$results=do_search($searchadd.$search,$restypes,$order_by,$archive,-1,$sort,false,$starsearch);

if (!is_array($results)){$results=array();}

$paginate=false;
if (getval("results_per_page","")!="" || getval("page","")!=""){
    $paginate=true;
    $results_per_page=getval("results_per_page",15);
    $page=getval("page",1);
    $min_result=($page-1)*$results_per_page;
    $max_result=($page*$results_per_page)-1;

    // build a new array with pagination info
    $pagination=array();
    $pagination["total_pages"]=ceil(count($results)/$results_per_page);
    
    // default to first page if an invalid page is given.
    if ($page>$pagination["total_pages"]){
        $page=1;
        $min_result=0;
        $max_result=$results_per_page-1;
    }
    
    $pagination["total_resources"]=count($results);
    $pagination["per_page"]=$results_per_page;
    $pagination["page"]=$page;
    

    /* commented out as it should probably be done application side
    // build a next/prev query strings for easier pagination:
    // $url=$_SERVER['REQUEST_URI'];
    // $urlparts=parse_url($url);

    // parse_str($urlparts['query'],$queryparts);
    // $newquery=array();
    // foreach ($queryparts as $key=>$value){
    //  if ($key!='page' && $key!='key' && $key!='results_per_page'){
    //  $newquery[$key]=$value;
    //  }
    // }
    // $newquery=http_build_query($newquery);
    // protocol = strtolower(substr($_SERVER["SERVER_PROTOCOL"],0,5))=='https'?'https':'http';
    // if (($page+1)<=$pagination["total_pages"]){
    //  $pagination["next_page"]=$newquery."&page=".($page+1);
    // }
    // if (($page-1)>0){
    //  $pagination["previous_page"]=$newquery."&page=".($page-1);
    // }
    */
    
    $newresult=array();
    for ($n=0;$n<count($results);$n++){
        if (($n>=$min_result) && $n<=$max_result){
        $newresult[]=$results[$n];
        }
    }
    $results=$newresult;
}

if (getval("previewsize","")!=""){
    for($n=0;$n<count($results);$n++){
        $access=get_resource_access($results[$n]);
        $use_watermark=check_use_watermark();
        $filepath=get_resource_path($results[$n]['ref'],true,getval('previewsize',''),false,'jpg',-1,1,$use_watermark,'',-1);
        $previewpath=get_resource_path($results[$n]['ref'],false,getval("previewsize",""),false,"jpg",-1,1,$use_watermark,"",-1);
        if (file_exists($filepath)){
            $results[$n]['preview']=$previewpath;
        }
        else {
            $previewpath=explode('filestore/',$previewpath);
            $previewpath=$previewpath[0]."gfx/";
            $file=$previewpath.get_nopreview_icon($results[$n]["resource_type"],$results[$n]["file_extension"],false,true);
            $results[$n]['preview']=$file;
        }
    }
}

// flv file and thumb if available
if (getval("flvfile","")!=""){
    for($n=0;$n<count($results);$n++){
        // flv previews
        $flvfile=get_resource_path($results[$n]['ref'],true,"pre",false,$ffmpeg_preview_extension);
        if (!file_exists($flvfile)) {$flvfile=get_resource_path($results[$n]['ref'],true,"",false,$ffmpeg_preview_extension);}
        if (!(isset($results[$n]['is_transcoding']) && $results[$n]['is_transcoding']==1) && file_exists($flvfile) && (strpos(strtolower($flvfile),".".$ffmpeg_preview_extension)!==false))
            {
            if (file_exists(get_resource_path($results[$n]['ref'],true,"pre",false,$ffmpeg_preview_extension)))
                {
                $flashpath=get_resource_path($results[$n]['ref'],false,"pre",false,$ffmpeg_preview_extension,-1,1,false,"",-1,false);
                }
            else 
                {
                $flashpath=get_resource_path($results[$n]['ref'],false,"",false,$ffmpeg_preview_extension,-1,1,false,"",-1,false);
                }
            $results[$n]['flvpath']=$flashpath;
            $thumb=get_resource_path($results[$n]['ref'],false,"pre",false,"jpg"); 
            $results[$n]['flvthumb']=$thumb;
        }
    }
}

if (getval("videosonly","")!=""){
	$newresult=array();
	for ($n=0;$n<count($results);$n++){
		if (isset($results[$n]["flvpath"]) && isset($results[$n]["flvthumb"])){
			$newresult[]=$results[$n];
		}
	}
	$results=$newresult;
}


$modified_result=hook("modifyapisearchresult");
if ($modified_result){
	$results=$modified_result;
}

 // this function in api_core   
$results=refine_api_resource_results($results);

if (getval("content","")=="xml" && !$paginate){
    header('Content-type: application/xml');
    echo '<?xml version="1.0" encoding="UTF-8"?><results>';
    foreach ($results as $result){
        echo '<resource>';
        foreach ($result as $resultitem=>$value){
            echo '<'.$resultitem.'>';
            echo xml_entities($value);
            echo '</'.$resultitem.'>';
        }
        echo '</resource>';
    }
    echo '</results>';
}

else if (getval("content","")=="xml" && $paginate){

	$resources=$results;
   
    header('Content-type: application/xml');
    echo '<?xml version="1.0" encoding="UTF-8"?>';
    echo '<results>';
    echo '<pagination>';

    foreach ($pagination as $resultitem=>$value){
        echo '<'.$resultitem.'>';
        echo xml_entities($value);
        echo '</'.$resultitem.'>';
    }

    
    echo '</pagination>';
    echo '<resources>';
    foreach ($resources as $result){
        echo '<resource>';
        foreach ($result as $resultitem=>$value){
            echo '<'.$resultitem.'>';
            echo xml_entities($value);
            echo '</'.$resultitem.'>';
        }
        echo '</resource>';
    }
    echo '</resources>';
	echo '</results>';
}

else {
    header('Content-type: application/json');
    if ($paginate) {
        $results = array('resources' => $results, 'pagination' => $pagination);
    }
    echo json_encode($results); // echo json without headers by default
} 




