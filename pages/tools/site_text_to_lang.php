<?php

# Quick script to fix database entries that need commas at the beginning (dropdown fields edited using collection edit before r1940). 
# When some values have commas and others don't, sorting doesn't work correctly!!!

include "../../include/db.php";
include "../../include/authenticate.php"; if (!checkperm("a")) {exit("Permission denied");}
include "../../include/general.php";
include "../../include/resource_functions.php";

$lang=getvalescaped("lang","en");

header("Content-type: text/plain; charset=utf-8");

$site_text=sql_query("select page,name,text from site_text where language='$lang' group by page,name");
foreach ($site_text as $s)
    {
    
    echo "\$lang[\"" . $s["page"] . "__" . $s["name"] . "\"]=\"" . str_replace("\n","\\n",addslashes($s["text"])) . "\";\n";
    
    }
    
