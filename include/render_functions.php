<?php
/**
* Functions used to render HTML & Javascript
*
* @package ResourceSpace
*/

/*
TO DO: add here other functions used for rendering such as:
- render_search_field from search_functions.php
*/

/**
* Renders sort order functionality as a dropdown box
*
*/
function render_sort_order(array $order_fields)
    {
    global $order_by, $baseurl_short, $search, $archive, $restypes, $k, $sort;
    ?>

    <select id="sort_order_selection">
    
    <?php
    $options = '';
    foreach($order_fields as $name => $label)
        {
        $fixed_order = $name == 'relevance';
        $selected    = $order_by == $name;

        // Build the option:
        $option = '<option value="' . $name . '"';

        if(($selected && $fixed_order) || $selected)
            {
            $option .= ' selected';
            }

        $option .= sprintf('
                data-url="%spages/search.php?search=%s&amp;order_by=%s&amp;archive=%s&amp;k=%s&amp;restypes=%s"
            ',
            $baseurl_short,
            urlencode($search),
            $name,
            urlencode($archive),
            urlencode($k),
            urlencode($restypes)
        );

        $option .= '>';
        $option .= $label;
        $option .= '</option>';

        // Add option to the options list
        $options .= $option;
        }

        hook('render_sort_order_add_option', '', array($options));
        echo $options;
    ?>
    
    </select>
    <select id="sort_selection">
        <option value="ASC" <?php if($sort == 'ASC') { echo 'selected';} ?>>ASC</option>
        <option value="DESC" <?php if($sort == 'DESC') { echo 'selected';} ?>>DESC</option>
    </select>
    
    <script>
    jQuery('#sort_order_selection').change(function() {
        var selected_option      = jQuery('#sort_order_selection option[value="' + this.value + '"]');
        var selected_sort_option = jQuery('#sort_selection option:selected').val();
        var option_url           = selected_option.data('url');

        if('ASC' === selected_sort_option)
            {
            option_url += '&sort=ASC';
            }

        CentralSpaceLoad(option_url);
    });

    jQuery('#sort_selection').change(function() {
        var selected_option                = this.value;
        var selected_sort_order_option     = jQuery('#sort_order_selection option:selected');
        var selected_sort_order_option_url = selected_sort_order_option.data('url');

        selected_sort_order_option_url += '&sort=' + selected_option;

        CentralSpaceLoad(selected_sort_order_option_url);
    });
    </script>
    <?php
    return;
    }

/**
* Renders a dropdown option
* 
*/
function render_dropdown_option($value, $label, array $data_attr = array(), $extra_tag_attributes  = '')
    {
    $result = '<option value="' . $value . '"';

    // Add any extra tag attributes
    if(trim($extra_tag_attributes) !== '')
        {
        $result .= ' ' . $extra_tag_attributes;
        }

    // Add any data attributes you may need
    foreach($data_attr as $data_attr_key => $data_attr_value)
        {
        $data_attr_key = str_replace(' ', '_', $data_attr_key);

        $result .= ' data-' . $data_attr_key . '="' . $data_attr_value . '"';
        }

    $result .= '>' . $label . '</option>';

    return $result;
    }


/**
* Renders search actions functionality as a dropdown box
* 
*/
function render_actions(array $collection_data, $top_actions = true, $two_line = true, $id = '')
    {
    if(hook('prevent_running_render_actions'))
        {
        return;
        }

    global $baseurl, $lang, $pagename;

    // globals that could also be passed as a reference
    global $result /*search result*/;

    $action_selection_id = $pagename . '_action_selection' . $id;
    if(!$top_actions)
        {
        $action_selection_id .= '_bottom';
        }
    if(isset($collection_data['ref']))
        {
        $action_selection_id .= '_' . $collection_data['ref'];
        }
        ?>

    <div class="ActionsContainer  <?php if($top_actions) { echo 'InpageNavLeftBlock'; } ?>">
        <div class="DropdownActionsLabel"><?php echo $lang['actions']; ?>:</div>
    <?php
    if($two_line)
        {
        ?>
        <br />
        <?php
        }
            ?>
        <select id="<?php echo $action_selection_id; ?>" <?php if(!$top_actions) { echo 'class="SearchWidth"'; } ?>>
            <option class="SelectAction" value=""></option>
            <?php
            $options = '';

            // Collection Actions
            $options .= render_collection_actions($collection_data, $top_actions);

            // Usual search actions    
            $options .= render_search_actions($top_actions);

            echo $options;
            ?>
        </select>
        <script>
        jQuery('#<?php echo $action_selection_id; ?>').change(function() {

            if(this.value == '')
                {
                return false;
                }

            switch(this.value)
                {
            <?php
            if(!empty($collection_data))
                {
                ?>
                case 'select_collection':
                    ChangeCollection(<?php echo $collection_data['ref']; ?>, '');
                    break;

                case 'remove_collection':
                    if(confirm("<?php echo $lang['removecollectionareyousure']; ?>")) {
                        // most likely will need to be done the same way as delete_collection
                        document.getElementById('collectionremove').value = '<?php echo urlencode($collection_data["ref"]); ?>';
                        document.getElementById('collectionform').submit();
                    }
                    break;

                case 'purge_collection':
                    if(confirm('<?php echo $lang["purgecollectionareyousure"]; ?>'))
                        {
                        document.getElementById('collectionpurge').value='".urlencode($collections[$n]["ref"])."';
                        document.getElementById('collectionform').submit();
                        }
                    break;
                <?php
                }

            if(!$top_actions || !empty($collection_data))
                {
                ?>
                case 'delete_collection':
                    if(confirm('<?php echo $lang["collectiondeleteconfirm"]; ?>')) {
                        var post_data = {
                            ajax: true,
                            dropdown_actions: true,
                            delete: <?php echo urlencode($collection_data['ref']); ?> 
                        };

                        jQuery.post('<?php echo $baseurl; ?>/pages/collection_manage.php', post_data, function(response) {
                            if(response.success === 'Yes')
                                {
                                CollectionDivLoad('<?php echo $baseurl; ?>/pages/collections.php?collection=' + response.redirect_to_collection + '&k=' + response.k + '&nc=' + response.nc);
                                CentralSpaceLoad('<?php echo $baseurl; ?>/pages/search.php?search=!collection' + response.redirect_to_collection, true);
                                }
                        }, 'json');    
                    }
                    break;
                <?php
                }

            // Add extra collection actions javascript case through plugins
            // Note: if you are just going to a different page, it should be easily picked by the default case
            $extra_options_js_case = hook('render_actions_add_option_js_case');
            if(trim($extra_options_js_case) !== '')
                {
                echo $extra_options_js_case;
                }
            ?>

                case 'save_search_to_collection':
                    var option_url = jQuery('#<?php echo $action_selection_id; ?> option:selected').data('url');
                    CollectionDivLoad(option_url);
                    break;

                case 'save_search_to_dash':
                    var option_url  = jQuery('#<?php echo $action_selection_id; ?> option:selected').data('url');
                    var option_link = jQuery('#<?php echo $action_selection_id; ?> option:selected').data('link');

                    // Dash requires to have some search paramenters (even if they are the default ones)
                    if((window.location.href).replace(window.baseurl, '') != '/pages/search.php')
                        {
                        option_link = (window.location.href).replace(window.baseurl, '');
                        }

                    option_url    += '&link=' + option_link;

                    CentralSpaceLoad(option_url);
                    break;

                case 'save_search_smart_collection':
                    var option_url = jQuery('#<?php echo $action_selection_id; ?> option:selected').data('url');
                    CollectionDivLoad(option_url);
                    break;

                case 'save_search_items_to_collection':
                    var option_url = jQuery('#<?php echo $action_selection_id; ?> option:selected').data('url');
                    CollectionDivLoad(option_url);
                    break;

                case 'empty_collection':
                    if(!confirm('<?php echo $lang["emptycollectionareyousure"]; ?>'))
                        {
                        break;
                        }

                    var option_url = jQuery('#<?php echo $action_selection_id; ?> option:selected').data('url');
                    CollectionDivLoad(option_url);
                    break;

            <?php
            if(!$top_actions)
                {
                ?>
                case 'delete_all_in_collection':
                    if(confirm('<?php echo $lang["deleteallsure"]; ?>'))
                        {
                        var post_data = {
                            submitted: true,
                            ref: '<?php echo $collection_data["ref"]; ?>',
                            name: '<?php echo urlencode($collection_data["name"]); ?>',
                            public: '<?php echo $collection_data["public"]; ?>',
                            deleteall: 'on'
                        };

                        jQuery.post('<?php echo $baseurl; ?>/pages/collection_edit.php?ajax=true', post_data, function()
                            {
                            CollectionDivLoad('<?php echo $baseurl; ?>/pages/collections.php?collection=<?php echo $collection_data["ref"] ?>');
                            });
                        }
                    break;
                <?php
                }
                ?>

                case 'csv_export_results_metadata':
                    var option_url = jQuery('#<?php echo $action_selection_id; ?> option:selected').data('url');
                    window.location.href = option_url;
                    break;

                default:
                    var option_url = jQuery('#<?php echo $action_selection_id; ?> option:selected').data('url');
                    CentralSpaceLoad(option_url, true);
                    break;
                }

                // Go back to no action option
                jQuery('#<?php echo $action_selection_id; ?> option[value=""]').attr('selected', 'selected');

        });
        </script>
    </div>
    
    <?php
    return;
    }


function render_collection_actions(array $collection_data, $top_actions)
    {
    global $baseurl_short, $lang, $k, $userrequestmode, $collection_download, $contact_sheet,
           $manage_collections_contact_sheet_link, $manage_collections_share_link, $allow_share,
           $manage_collections_remove_link, $userref, $collection_purge, $show_edit_all_link, $result,
           $edit_all_checkperms, $preview_all, $order_by, $sort, $archive, $contact_sheet_link_on_collection_bar,
           $show_searchitemsdiskusage, $emptycollection, $remove_resources_link_on_collection_bar, $count_result,
           $download_usage, $home_dash, $top_nav_upload_type, $pagename;

    $options = '';

    if(empty($collection_data))
        {
        return $options;
        }


    if(!collection_is_research_request($collection_data['ref']) || !checkperm('r'))
        {
        if(!$top_actions && checkperm('s'))
            {
            // Manage My Collections
            $data_attribute['url'] = $baseurl_short . 'pages/collection_manage.php';
            $options .= render_dropdown_option('manage_collections', $lang['managemycollections'], $data_attribute);

            // Collection feedback
            if(isset($collection_data['request_feedback']) && $collection_data['request_feedback'])
                {
                $data_attribute['url'] = sprintf('%spages/collection_feedback.php?collection=%s&k=%s',
                    $baseurl_short,
                    urlencode($collection_data['ref']),
                    urlencode($k)
                );
                $options .= render_dropdown_option('collection_feedback', $lang['sendfeedback'], $data_attribute);
                }
            }
        }
    else
        {
        $research = sql_value('SELECT ref value FROM research_request WHERE collection="' . $collection_data['ref'] . '";', 0);

        // Manage research requests
        $data_attribute['url'] = sprintf('%spages/team/team_research.php', $baseurl_short);
        $options .= render_dropdown_option('manage_research_requests', $lang['manageresearchrequests'], $data_attribute);

        // Edit research requests
        $data_attribute['url'] = sprintf('%spages/team/team_research_edit.php?ref=%s',
            $baseurl_short,
            urlencode($research)
        );
        $options .= render_dropdown_option('edit_research_requests', $lang['editresearchrequests'], $data_attribute);
        }

    // Select collection option - not for collection bar
    if($pagename!="collections" && $k == '' && !checkperm('b') && ($pagename=="themes" || $top_actions))
        {
        $options .= render_dropdown_option('select_collection', $lang['selectcollection']);
        }

    // Edit Collection
    if(($userref == $collection_data['user']) || (checkperm('h'))) 
        {
        $extra_tag_attributes = sprintf('
                data-url="%spages/collection_edit.php?ref=%s"
            ',
            $baseurl_short,
            urlencode($collection_data['ref'])
        );

        $options .= render_dropdown_option('edit_collection', $lang['action-edit'], array(), $extra_tag_attributes);
        }

    // Upload to collection
    if((checkperm('c') || checkperm('d')) && $collection_data['savedsearch'] == 0 && ($userref == $collection_data['user'] || $collection_data['allow_changes'] == 1 || checkperm('h')))
        {
        $data_attribute['url'] = sprintf('%spages/edit.php?uploader=%s&ref=-%s&collection_add=%s',
            $baseurl_short,
            urlencode($top_nav_upload_type),
            urlencode($userref),
            urlencode($collection_data['ref'])
        );

        $options .= render_dropdown_option('upload_collection', $lang['action-upload-to-collection'], $data_attribute);
        }

    // Home_dash is on, AND NOT Anonymous use, AND (Dash tile user (NOT with a managed dash) || Dash Tile Admin)
    if(!$top_actions && $home_dash && checkPermission_dashcreate())
        {
        $data_attribute['url'] = sprintf('
            %spages/dash_tile.php?create=true&tltype=srch&promoted_resource=true&freetext=true&all_users=1&link=/pages/search.php?search=!collection%s&order_by=relevance&sort=DESC
            ',
            $baseurl_short,
            $collection_data['ref']
        );

        $options .= render_dropdown_option('save_collection_to_dash', $lang['createnewdashtile'], $data_attribute);
        }

    // Request all
    if($count_result > 0)
        {
        # Ability to request a whole collection (only if user has restricted access to any of these resources)
        $min_access = collection_min_access($result);
        if($min_access != 0)
            {
            $data_attribute['url'] = sprintf('%spages/collection_request.php?ref=%s&k=%s',
                $baseurl_short,
                urlencode($collection_data['ref']),
                urlencode($k)
            );
            $options .= render_dropdown_option('request_all', $lang['requestall'], $data_attribute);
            }
        }

    // Download option
    if($download_usage && ((isset($zipcommand) || $collection_download) && $count_result > 0))
        {
        $data_attribute['url'] = sprintf('%spages/terms.php?k=%s&url=pages/download_usage.php?collection=%s&k=%s',
            $baseurl_short,
            urlencode($k),
            urlencode($collection_data['ref']),
            urlencode($k)
        );
        $options .= render_dropdown_option('download_collection', $lang['action-download'], $data_attribute);
        }
    else if((isset($zipcommand) || $collection_download) && $count_result > 0)
        {
        $data_attribute['url'] = sprintf('%spages/terms.php?k=%s&url=pages/collection_download.php?collection=%s&k=%s',
            $baseurl_short,
            urlencode($k),
            urlencode($collection_data['ref']),
            urlencode($k)
        );
        $options .= render_dropdown_option('download_collection', $lang['action-download'], $data_attribute);
        }
    else if(isset($zipcommand) || $collection_download) 
        {
        $data_attribute['url'] = sprintf('%spages/terms.php?url=pages/collection_download.php?collection=%s',
            $baseurl_short,
            urlencode($collection_data['ref'])
        );
        $options .= render_dropdown_option('download_collection', $lang['action-download'], $data_attribute);
        }

    // Contact Sheet
    if($contact_sheet == true && ($manage_collections_contact_sheet_link || $contact_sheet_link_on_collection_bar))
        {
        $data_attribute = array(
            'url' => sprintf('%spages/contactsheet_settings.php?ref=%s',
                $baseurl_short,
                urlencode($collection_data['ref'])
            )
        );

        $options .= render_dropdown_option('contact_sheet', $lang['contactsheet'], $data_attribute);
        }

    // Share
    if($manage_collections_share_link && $allow_share && (checkperm('v') || checkperm ('g'))) 
        {
        $extra_tag_attributes = sprintf('
                data-url="%spages/collection_share.php?ref=%s"
            ',
            $baseurl_short,
            urlencode($collection_data['ref'])
        );

        $options .= render_dropdown_option('share_collection', $lang['share'], array(), $extra_tag_attributes);
        }

    // Remove
    if($manage_collections_remove_link && $userref != $collection_data['user'])
        {
        $options .= render_dropdown_option('remove_collection', $lang['action-remove']);
        }

    // Delete
    if((($userref == $collection_data['user']) || checkperm('h')) && ($collection_data['cant_delete'] == 0)) 
        {
        $options .= render_dropdown_option('delete_collection', $lang['action-delete']);
        }

    // Collection Purge
    if($collection_purge && isset($collections) && checkperm('e0') && $collection_data['cant_delete'] == 0)
        {
        $options .= render_dropdown_option('purge_collection', $lang['purgeanddelete']);
        }

    // Collection log
    if(($userref== $collection_data['user']) || (checkperm('h')))
        {
        $extra_tag_attributes = sprintf('
                data-url="%spages/collection_log.php?ref=%s"
            ',
            $baseurl_short,
            urlencode($collection_data['ref'])
        );

        $options .= render_dropdown_option('collection_log', $lang['action-log'], array(), $extra_tag_attributes);
        }
        
    // View all
    if((isset($collection_data["c"]) && $collection_data["c"]>0) || count($result) > 0)
        {
        $data_attribute['url'] = sprintf('%spages/search.php?search=!collection%s',
            $baseurl_short,
            urlencode($collection_data['ref'])
        );

        $options .= render_dropdown_option('view_all_resources_in_collection', $lang['view_all_resources'], $data_attribute);
        }

    // Edit all
    # If this collection is (fully) editable, then display an edit all link
    if($show_edit_all_link && (count($result) > 0))
        {
        if(!$edit_all_checkperms || allow_multi_edit($collection_data['ref'])) 
            {
            $extra_tag_attributes = sprintf('
                    data-url="%spages/edit.php?collection=%s"
                ',
                $baseurl_short,
                urlencode($collection_data['ref'])
            );

            $options .= render_dropdown_option('edit_all_in_collection', $lang['edit_all_resources'], array(), $extra_tag_attributes);
            }
        }

    // Delete all
    // Note: functionality moved from edit collection page
    if(!$top_actions && !checkperm('D') && (count($result) != 0 || $count_result != 0)) 
        {
        $options .= render_dropdown_option('delete_all_in_collection', $lang['deleteallresourcesfromcollection']);
        }

    // Preview all
    if(count($result) != 0 && $k == '' && $preview_all)
        {
        $extra_tag_attributes = sprintf('
                data-url="%spages/preview_all.php?ref=%s"
            ',
            $baseurl_short,
            urlencode($collection_data['ref'])
        );

        $options .= render_dropdown_option('preview_all', $lang['preview_all'], array(), $extra_tag_attributes);
        }

    // Remove all
    if(isset($emptycollection) && $remove_resources_link_on_collection_bar && collection_writeable($collection_data['ref']))
        {
        $data_attribute['url'] = sprintf('%spages/collections.php?emptycollection=%s&removeall=true&submitted=removeall&ajax=true',
            $baseurl_short,
            urlencode($collection_data['ref'])
        );

        $options .= render_dropdown_option('empty_collection', $lang['emptycollection'], $data_attribute);
        }

    // Show disk usage
    if(!$top_actions && $show_searchitemsdiskusage) 
        {
        $extra_tag_attributes = sprintf('
                data-url="%spages/search_disk_usage.php?search=!collection%s&k=%s"
            ',
            $baseurl_short,
            urlencode($collection_data['ref']),
            urlencode($k)
        );

        $options .= render_dropdown_option('search_items_disk_usage', $lang['collection_disk_usage'], array(), $extra_tag_attributes);
        }

    // Add extra collection actions through plugins
    $extra_options = hook('render_actions_add_collection_option');
    if(trim($extra_options) !== '')
        {
        $options .= $extra_options;
        }

    return $options;
    }


function render_search_actions($top_actions)
    {
    $options = '';

    global $baseurl_short, $lang, $k, $search, $restypes, $order_by, $archive, $sort, $daylimit, $home_dash, $url,
           $allow_smart_collections, $resources_count, $show_searchitemsdiskusage, $offset, $allow_save_search;

    // globals that could also be passed as a reference
    global $starsearch;

    if(!checkperm('b') && $k == '') 
        {
        if($top_actions && $allow_save_search)
            {
            $extra_tag_attributes = sprintf('
                    data-url="%spages/collections.php?addsearch=%s&restypes=%s&archive=%s&daylimit=%s"
                ',
                $baseurl_short,
                urlencode($search),
                urlencode($restypes),
                urlencode($archive),
                urlencode($daylimit)
            );

            $options .= render_dropdown_option('save_search_to_collection', $lang['savethissearchtocollection'], array(), $extra_tag_attributes);
            }

        #Home_dash is on, AND NOT Anonymous use, AND (Dash tile user (NOT with a managed dash) || Dash Tile Admin)
        if($top_actions && $home_dash && checkPermission_dashcreate())
            {
            $option_name = 'save_search_to_dash';
            $data_attribute = array(
                'url'  => $baseurl_short . 'pages/dash_tile.php?create=true&tltype=srch&freetext=true"',
                'link' => $url
            );

            if(substr($search, 0, 11) == '!collection')
                {
                $option_name = 'save_collection_to_dash';
                $data_attribute['url'] = sprintf('
                    %spages/dash_tile.php?create=true&tltype=srch&promoted_resource=true&freetext=true&all_users=1&link=/pages/search.php?search=%s&order_by=relevance&sort=DESC
                    ',
                    $baseurl_short,
                    $search
                );
                }

            $options .= render_dropdown_option($option_name, $lang['savethissearchtodash'], $data_attribute);
            }

        // Save search as Smart Collections
        if($allow_smart_collections && substr($search, 0, 11) != '!collection')
            {
            $extra_tag_attributes = sprintf('
                    data-url="%spages/collections.php?addsmartcollection=%s&restypes=%s&archive=%s&starsearch=%s"
                ',
                $baseurl_short,
                urlencode($search),
                urlencode($restypes),
                urlencode($archive),
                urlencode($starsearch)
            );

            $options .= render_dropdown_option('save_search_smart_collection', $lang['savesearchassmartcollection'], array(), $extra_tag_attributes);
            }

        /*// Wasn't able to see this working even in the old code
        // so I left it here for reference. Just uncomment it and it should work
        global $smartsearch;
        if($allow_smart_collections && substr($search, 0, 11) == '!collection' && (is_array($smartsearch[0]) && !empty($smartsearch[0])))
            {
            $smartsearch = $smartsearch[0];

            $extra_tag_attributes = sprintf('
                    data-url="%spages/search.php?search=%s&restypes=%s&archive=%s&starsearch=%s&daylimit=%s"
                ',
                $baseurl_short,
                urlencode($smartsearch['search']),
                urlencode($smartsearch['restypes']),
                urlencode($smartsearch['archive']),
                urlencode($smartsearch['starsearch']),
                urlencode($daylimit)
            );

            $options .= render_dropdown_option('do_saved_search', $lang['dosavedsearch'], array(), $extra_tag_attributes);
            }*/

        if($resources_count != 0)
            {
            $extra_tag_attributes = sprintf('
                    data-url="%spages/collections.php?addsearch=%s&restypes=%s&archive=%s&mode=resources&daylimit=%s"
                ',
                $baseurl_short,
                urlencode($search),
                urlencode($restypes),
                urlencode($archive),
                urlencode($daylimit)
            );

            $options .= render_dropdown_option('save_search_items_to_collection', $lang['savesearchitemstocollection'], array(), $extra_tag_attributes);

            if($show_searchitemsdiskusage) 
                {
                $extra_tag_attributes = sprintf('
                        data-url="%spages/search_disk_usage.php?search=%s&restypes=%s&offset=%s&order_by=%s&sort=%s&archive=%s&daylimit=%s&k=%s"
                    ',
                    $baseurl_short,
                    urlencode($search),
                    urlencode($restypes),
                    urlencode($offset),
                    urlencode($order_by),
                    urlencode($sort),
                    urlencode($archive),
                    urlencode($daylimit),
                    urlencode($k)
                );

                $options .= render_dropdown_option('search_items_disk_usage', $lang['searchitemsdiskusage'], array(), $extra_tag_attributes);
                }
            }
        }

    if($top_actions && $k == '')
        {
        $extra_tag_attributes = sprintf('
                data-url="%spages/csv_export_results_metadata.php?search=%s&restype=%s&order_by=%s&archive=%s&sort=%s&starsearch=%s"
            ',
            $baseurl_short,
            urlencode($search),
            urlencode($restypes),
            urlencode($order_by),
            urlencode($archive),
            urlencode($sort),
            urlencode($starsearch)

        );

        $options .= render_dropdown_option('csv_export_results_metadata', $lang['csvExportResultsMetadata'], array(), $extra_tag_attributes);
        }

    // Add extra search actions through plugins
    $extra_options = hook('render_search_actions_add_option');
    if($top_actions && trim($extra_options) !== '')
        {
        $options .= $extra_options;
        }

    return $options;
    }
