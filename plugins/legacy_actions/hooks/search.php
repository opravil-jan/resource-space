<?php
include_once __DIR__ . '/../include/search_functions.php';

function HookLegacy_actionsSearchRender_sort_order_differently($orderFields)
    {
    foreach($orderFields as $order => $label)
        {
        display_sort_order($order, $label);
        }

    return true;
    }

function HookLegacy_actionsSearchPrevent_running_render_actions()
	{
	return true;
	}

function HookLegacy_actionsSearchAdd_search_title_links()
    {
	global $baseurl, $baseurl_short, $lang, $search, $k, $userrequestmode, $collection_download, $contact_sheet,
           $manage_collections_contact_sheet_link, $manage_collections_share_link, $allow_share,
           $manage_collections_remove_link, $username, $collection_purge, $show_edit_all_link, $edit_all_checkperms,
           $preview_all, $order_by, $sort, $archive, $collectiondata, $result, $search_title, $display, $search_title_links;

	// extra collection title links
	if(substr($search, 0, 11) == "!collection")
		{
		if($k == "" && !checkperm("b") && ($userrequestmode != 2 && $userrequestmode != 3))
			{
			$search_title_links = "<div class='CollectionTitleLinks'>";
			
			if($k == "" && !checkperm("b") && ($userrequestmode != 2 && $userrequestmode != 3))
				{
				$search_title_links.='<a href="#" onclick="ChangeCollection(' . $collectiondata["ref"] . ', \'\');">&gt;&nbsp;'.$lang["selectcollection"].'</a>';
				}
			
			if(isset($zipcommand) || $collection_download) 
				{
				$search_title_links.="<a onClick='return CentralSpaceLoad(this,true);' href='" . $baseurl_short . "pages/terms.php?url=" . urlencode("pages/collection_download.php?collection=" . $collectiondata["ref"])."'>&gt;&nbsp;".$lang["action-download"]."</a>";
				}
			
			if($contact_sheet == true && $manage_collections_contact_sheet_link) 
				{
				$search_title_links.="<a onClick='return CentralSpaceLoad(this,true);' href='" . $baseurl_short . "pages/contactsheet_settings.php?ref=" . urlencode($collectiondata["ref"]) . "'>&gt;&nbsp;".$lang["contactsheet"]."</a>";
				}
			
			if ($manage_collections_share_link && $allow_share && (checkperm("v") || checkperm ("g"))) 
				{
				$search_title_links.="&nbsp;<a href='".$baseurl_short."pages/collection_share.php?ref=" . $collectiondata["ref"] . "' onClick='return CentralSpaceLoad(this,true);'>&gt;&nbsp;".$lang["share"]."</a>";
				}
			
			if($manage_collections_remove_link && $username != $collectiondata["username"])
				{
				$search_title_links.="&nbsp;<a href='#' onclick=\" if(confirm('".$lang["removecollectionareyousure"]."')){document.getElementById('collectionremove').value='" . urlencode($collectiondata["ref"]) . "';document.getElementById('collectionform').submit();} return false;\">&gt;&nbsp;".$lang["action-remove"]."</a>";
				}
			
			if((($username==$collectiondata["username"]) || checkperm("h")) && ($collectiondata["cant_delete"]==0)) 
				{
				$search_title_links.="&nbsp;<a href='#'' onclick=\"if (confirm('".$lang["collectiondeleteconfirm"]."')) {document.getElementById('collectiondelete').value='" . urlencode($collectiondata["ref"]) . "';CentralSpacePost(document.getElementById('collectionform'),false);} return false;\">&gt;&nbsp;".$lang["action-delete"]."</a>";
				}

			if($collection_purge)
				{ 
				if($n == 0) 
					{
					$search_title_links.="<input type=hidden name='purge' id='collectionpurge' value=''>"; 
					}
				if(isset($collections) && checkperm("e0") && $collectiondata["cant_delete"] == 0) 
					{
					$search_title_links.="&nbsp;<a href='#' onclick=\"if (confirm('".$lang["purgecollectionareyousure"]."')){document.getElementById('collectionpurge').value='".urlencode($collections[$n]["ref"])."';document.getElementById('collectionform').submit();} return false;\">&gt;&nbsp;".$lang["purgeanddelete"]."</a>"; 
					}
				}

			hook('additionalcollectiontool');
			
			if(($username == $collectiondata["username"]) || (checkperm("h"))) 
				{
				$search_title_links.="<a href='".$baseurl_short."pages/collection_edit.php?ref=" . urlencode($collectiondata["ref"]) . "' onClick='return CentralSpaceLoad(this,true);' >&gt;&nbsp;".$lang["action-edit"]."</a>";
				}
			
			# If this collection is (fully) editable, then display an edit all link
			if($show_edit_all_link && (count($result) > 0))
				{
				if(!$edit_all_checkperms || allow_multi_edit($collectiondata["ref"])) 
					{ 
					$search_title_links.="&nbsp;<a href='".$baseurl_short."pages/edit.php?collection=" . urlencode($collectiondata["ref"]) . "' onClick='return CentralSpaceLoad(this,true);'>&gt;&nbsp;".$lang["action-editall"]."</a>";
					} 
				}

			if(($username == $collectiondata["username"]) || (checkperm("h"))) 
				{
				$search_title_links.="<a href='".$baseurl_short."pages/collection_log.php?ref=" . urlencode($collectiondata["ref"]) . "' onClick='return CentralSpaceLoad(this,true);'>&gt;&nbsp;".$lang["log"]."</a>"; 
				}

			hook("addcustomtool");
			
			$search_title_links.="</div>";
			// END INSERT
			}

		if(count($result) != 0 && $k == "" && $preview_all)
			{
			$search_title_links.='<a href="' . $baseurl_short.'pages/preview_all.php?ref=' . $collectiondata["ref"] . '&amp;order_by=' . urlencode($order_by) . '&amp;sort=' . urlencode($sort) . '&amp;archive=' . urlencode($archive) . '&amp;k=' . urlencode($k) . '">&gt;&nbsp;'.$lang['preview_all'].'</a>';
			}

		$search_title .= '</div>';
		if($display != "list")
			{
			$search_title_links .= '<br />';
			}
		}

    }