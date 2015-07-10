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
function render_actions()
    {
    if(hook('prevent_running_render_actions'))
        {
        return;
        }

    global $baseurl, $baseurl_short, $lang, $search, $k, $userrequestmode, $collection_download, $contact_sheet,
           $manage_collections_contact_sheet_link, $manage_collections_share_link, $allow_share,
           $manage_collections_remove_link, $username, $collection_purge, $show_edit_all_link, $edit_all_checkperms,
           $preview_all, $order_by, $sort, $archive, $allow_save_search;

    // globals that could also be passed as a reference
    global $collectiondata, $result /*search result*/;
    ?>
    
    <div class="InpageNavLeftBlock">
        Actions:
        <br />
        <select id="action_selection">
            <option value=""></option>
            <?php
            $options = '';

            // Collection Actions
            if(substr($search, 0, 11) == '!collection')
                {
                // Select collection option
                if($k == '' && !checkperm('b') && ($userrequestmode != 2 && $userrequestmode != 3))
                    {
                    $options .= render_dropdown_option('select_collection', $lang['selectcollection']);
                    }

                // Download option
                if(isset($zipcommand) || $collection_download) 
                    {
                    $data_attribute = array(
                        'url' => sprintf('%spages/terms.php?url=%s',
                            $baseurl_short,
                            urlencode('pages/collection_download.php?collection=' . $collectiondata['ref'])
                        )
                    );

                    $options .= render_dropdown_option('download_collection', $lang['action-download'], $data_attribute);
                    }

                // Contact Sheet
                if($contact_sheet == true && $manage_collections_contact_sheet_link) 
                    {
                    $data_attribute = array(
                        'url' => sprintf('%spages/contactsheet_settings.php?ref=%s',
                            $baseurl_short,
                            urlencode($collectiondata['ref'])
                        )
                    );

                    $options .= render_dropdown_option('contact_sheet', $lang['contactsheet'], $data_attribute);
                    }

                // Share
                if($manage_collections_share_link && $allow_share && (checkperm('v') || checkperm ('g'))) 
                    {
                    $extra_tag_attributes  = '';
                    $extra_tag_attributes .= sprintf('
                            data-url="%spages/collection_share.php?ref=%s"
                        ',
                        $baseurl_short,
                        urlencode($collectiondata['ref'])
                    );

                    $options .= '<option value="share_collection" ' . $extra_tag_attributes . '>' . $lang['share'] . '</option>';
                    }

                // Remove
                if($manage_collections_remove_link && $username != $collectiondata['username'])
                    {
                    $options .= '<option value="remove_collection">' . $lang['action-remove'] . '</option>';
                    }

                // Delete
                if((($username == $collectiondata['username']) || checkperm('h')) && ($collectiondata['cant_delete'] == 0)) 
                    {
                    $options .= '<option value="delete_collection">' . $lang['action-delete'] . '</option>';
                    }

                // Collection Purge
                if($collection_purge && isset($collections) && checkperm('e0') && $collectiondata['cant_delete'] == 0)
                    {
                    $options .= '<option value="purge_collection">' . $lang['purgeanddelete'] . '</option>';
                    }

                // Edit Collection
                if(($username == $collectiondata['username']) || (checkperm('h'))) 
                    {
                    $extra_tag_attributes  = '';
                    $extra_tag_attributes .= sprintf('
                            data-url="%spages/collection_edit.php?ref=%s"
                        ',
                        $baseurl_short,
                        urlencode($collectiondata['ref'])
                    );

                    $options .= '<option value="edit_collection" ' . $extra_tag_attributes . '>' . $lang['action-edit'] . '</option>';
                    }

                // Edit all
                # If this collection is (fully) editable, then display an edit all link
                if($show_edit_all_link && (count($result) > 0))
                    {
                    if(!$edit_all_checkperms || allow_multi_edit($collectiondata['ref'])) 
                        {
                        $extra_tag_attributes  = '';
                        $extra_tag_attributes .= sprintf('
                                data-url="%spages/edit.php?collection=%s"
                            ',
                            $baseurl_short,
                            urlencode($collectiondata['ref'])
                        );

                        $options .= '<option value="edit_all_in_collection" ' . $extra_tag_attributes . '>' . $lang['action-editall'] . '</option>';
                        }
                    }

                // Collection log
                if(($username == $collectiondata['username']) || (checkperm('h')))
                    {
                    $extra_tag_attributes  = '';
                    $extra_tag_attributes .= sprintf('
                            data-url="%spages/collection_log.php?ref=%s"
                        ',
                        $baseurl_short,
                        urlencode($collectiondata['ref'])
                    );

                    $options .= '<option value="collection_log" ' . $extra_tag_attributes . '>' . $lang['log'] . '</option>';
                    }

                // Preview all
                if(count($result) != 0 && $k == '' && $preview_all)
                    {
                    $extra_tag_attributes  = '';
                    $extra_tag_attributes .= sprintf('
                            data-url="%spages/preview_all.php?ref=%s&order_by=%s&sort=%s&archive=%s&k=%s"
                        ',
                        $baseurl_short,
                        urlencode($collectiondata['ref']),
                        urlencode($order_by),
                        urlencode($sort),
                        urlencode($archive),
                        urlencode($k)
                    );

                    $options .= '<option value="preview_all" ' . $extra_tag_attributes . '>' . $lang['preview_all'] . '</option>';
                    }

                // Add extra collection actions through plugins
                $extra_options = hook('render_actions_add_collection_option');
                if(trim($extra_options) !== '')
                    {
                    $options .= $extra_options;
                    }

                }

            // Usual search actions
            global $restypes, $daylimit, $home_dash, $url, $allow_smart_collections, $resources_count, $show_searchitemsdiskusage, $offset;
            
            // globals that could also be passed as a reference
            global $starsearch;

            if(!checkperm('b') && $k == '') 
                {
                if($allow_save_search)
                    {
                    $extra_tag_attributes  = '';
                    $extra_tag_attributes .= sprintf('
                            data-url="%spages/collections.php?addsearch=%s&restypes=%s&archive=%s&daylimit=%s"
                        ',
                        $baseurl_short,
                        urlencode($search),
                        urlencode($restypes),
                        urlencode($archive),
                        urlencode($daylimit)
                    );

                    $options .= '<option value="save_search_to_collection" ' . $extra_tag_attributes . '>' . $lang['savethissearchtocollection'] . '</option>';
                    }

                #Home_dash is on, AND NOT Anonymous use, AND (Dash tile user (NOT with a managed dash) || Dash Tile Admin)
                if($home_dash && checkPermission_dashcreate())
                    {
                    $extra_tag_attributes  = '';
                    $extra_tag_attributes .= 'data-url="' . $baseurl_short . 'pages/dash_tile.php?create=true&tltype=srch&freetext=true"';
                    $extra_tag_attributes .= ' data-link="' . $url . '"';

                    $options .= '<option value="save_search_to_dash" ' . $extra_tag_attributes . '>' . $lang['savethissearchtodash'] . '</option>';
                    }

                // Save search as Smart Collections
                if($allow_smart_collections && substr($search, 0, 11) != '!collection')
                    {
                    $extra_tag_attributes  = '';
                    $extra_tag_attributes .= sprintf('
                            data-url="%spages/collections.php?addsmartcollection=%s&restypes=%s&archive=%s&starsearch=%s"
                        ',
                        $baseurl_short,
                        urlencode($search),
                        urlencode($restypes),
                        urlencode($archive),
                        urlencode($starsearch)
                    );

                    $options .= '<option value="save_search_smart_collection" ' . $extra_tag_attributes . '>' . $lang['savesearchassmartcollection'] . '</option>';
                    }

                /* Wasn't able to see this working even in the old code
                // so I left it here for reference. Just uncomment it and it should work
                global $smartsearch;
                if($allow_smart_collections && substr($search, 0, 11) == '!collection' && (is_array($smartsearch[0]) && !empty($smartsearch[0])))
                    {
                    $smartsearch = $smartsearch[0];

                    $extra_tag_attributes  = '';
                    $extra_tag_attributes .= sprintf('
                            data-url="%spages/search.php?search=%s&restypes=%s&archive=%s&starsearch=%s&daylimit=%s"
                        ',
                        $baseurl_short,
                        urlencode($smartsearch['search']),
                        urlencode($smartsearch['restypes']),
                        urlencode($smartsearch['archive']),
                        urlencode($smartsearch['starsearch']),
                        urlencode($daylimit)
                    );

                    $options .= '<option value="do_saved_search" ' . $extra_tag_attributes . '>' . $lang['dosavedsearch'] . '</option>';
                    }*/

                if($resources_count != 0)
                    {
                    $extra_tag_attributes  = '';
                    $extra_tag_attributes .= sprintf('
                            data-url="%spages/collections.php?addsearch=%s&restypes=%s&archive=%s&mode=resources&daylimit=%s"
                        ',
                        $baseurl_short,
                        urlencode($search),
                        urlencode($restypes),
                        urlencode($archive),
                        urlencode($daylimit)
                    );

                    $options .= '<option value="save_search_items_to_collection" ' . $extra_tag_attributes . '>' . $lang['savesearchitemstocollection'] . '</option>';

                    if($show_searchitemsdiskusage) 
                        {
                        $extra_tag_attributes  = '';
                        $extra_tag_attributes .= sprintf('
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

                        $options .= '<option value="search_items_disk_usage" ' . $extra_tag_attributes . '>' . $lang['searchitemsdiskusage'] . '</option>';
                        }
                    }
                }

                if($k == '')
                    {
                    $extra_tag_attributes  = '';
                    $extra_tag_attributes .= sprintf('
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

                    $options .= '<option value="csv_export_results_metadata" ' . $extra_tag_attributes . '>' . $lang['csvExportResultsMetadata'] . '</option>';
                    }

            echo $options;
            ?>
        </select>
        <script>
        jQuery('#action_selection').change(function() {

            if(this.value == '')
                {
                return false;
                }

            switch(this.value)
                {
            <?php
            if(substr($search, 0, 11) == '!collection')
                {
                ?>
                case 'select_collection':
                    ChangeCollection(<?php echo $collectiondata['ref']; ?>, '');
                    break;

                case 'remove_collection':
                    if(confirm("<?php echo $lang['removecollectionareyousure']; ?>")) {
                        // I suspect this to not work but not sure what it should do
                        // /pages/collection_manage.php
                        // <input type="hidden" name="remove" id="collectionremove" value="">
                        // most likely will need to be done the same way as delete_collection
                        document.getElementById('collectionremove').value = '<?php echo urlencode($collectiondata["ref"]); ?>';
                        document.getElementById('collectionform').submit();
                    }
                    break;

                case 'delete_collection':
                    if(confirm('<?php echo $lang["collectiondeleteconfirm"]; ?>')) {
                        var post_data = {
                            ajax: true,
                            dropdown_actions: true,
                            delete: <?php echo urlencode($collectiondata['ref']); ?> 
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

                case 'purge_collection':
                    if(confirm('<?php echo $lang["purgecollectionareyousure"]; ?>'))
                        {
                        document.getElementById('collectionpurge').value='".urlencode($collections[$n]["ref"])."';
                        document.getElementById('collectionform').submit();
                        }
                    break;

                <?php
                }

            // Add extra collection actions javascript case through plugins
            // Note: if you are just going to a different page, it should be easily picked by the default case
            $extra_options_js_case = hook('render_actions_add_collection_option_js_case');
            if(trim($extra_options_js_case) !== '')
                {
                echo $extra_options_js_case;
                }
            ?>

                case 'save_search_to_collection':
                    var option_url = jQuery('#action_selection option:selected').data('url');
                    CollectionDivLoad(option_url);
                    break;

                case 'save_search_to_dash':
                    var option_url  = jQuery('#action_selection option:selected').data('url');
                    var option_link = jQuery('#action_selection option:selected').data('link');

                    // Dash requires to have some search paramenters (even if they are the default ones)
                    if((window.location.href).replace(window.baseurl, '') != '/pages/search.php')
                        {
                        option_link = (window.location.href).replace(window.baseurl, '');
                        }

                    option_url    += '&link=' + option_link;

                    CentralSpaceLoad(option_url);
                    break;

                case 'save_search_smart_collection':
                    var option_url = jQuery('#action_selection option:selected').data('url');
                    CollectionDivLoad(option_url);
                    break;

                case 'save_search_items_to_collection':
                    var option_url = jQuery('#action_selection option:selected').data('url');
                    CollectionDivLoad(option_url);
                    break;

                case 'csv_export_results_metadata':
                    var option_url = jQuery('#action_selection option:selected').data('url');
                    window.location.href = option_url;
                    break;

                default:
                    /* It should handle:
                    * download_collection
                    * contact_sheet
                    * share_collection
                    * edit_collection
                    * edit_all_in_collection
                    * collection_log
                    * preview_all
                    *
                    * Usual search actions:
                    * search_items_disk_usage
                    */
                    var option_url = jQuery('#action_selection option:selected').data('url');
                    CentralSpaceLoad(option_url, true);
                    break;
                }

                // Go back to no action option
                jQuery('#action_selection option[value=""]').attr('selected', 'selected');

        });
        </script>
    </div>
    
    <?php
    return;
    }