<?php

include "../../include/db.php";
include "../../include/general.php";
include "../../include/authenticate.php";
include_once '../../include/config_functions.php';

// Do not allow access to anonymous users
if(isset($anonymous_login) && ($anonymous_login == $username))
    {
    header('HTTP/1.1 401 Unauthorized');
    die('Permission denied!');
    }

$userpreferences_plugins= array();
$plugin_names=array();
$plugins_dir = dirname(__FILE__)."/../../plugins/";
foreach($active_plugins as $plugin)
    {
    $plugin = $plugin["name"];
    array_push($plugin_names,trim(mb_strtolower($plugin)));
    $plugin_yaml = get_plugin_yaml($plugins_dir.$plugin.'/'.$plugin.'.yaml', false);
    if(isset($plugin_yaml["userpreferencegroup"]))
        {
        $upg = trim(mb_strtolower($plugin_yaml["userpreferencegroup"]));
        $userpreferences_plugins[$upg][$plugin]=$plugin_yaml;
        }
    }

if(getvalescaped("quicksave",FALSE))
    {
    $ctheme = getvalescaped("colour_theme","");
    if($ctheme==""){exit("missing");}
    $ctheme = preg_replace("/^col-/","",trim(mb_strtolower($ctheme)));
    if($ctheme =="default")
        {
        if(empty($userpreferences))
            {
            // create a record
            sql_query("INSERT INTO user_preferences (user, parameter, `value`) VALUES (" . $userref . ", 'colour_theme', NULL);");
            rs_setcookie("colour_theme", "",100, "/", "", substr($baseurl,0,5)=="https", true);
            exit("1");
            }
        else
            {
            sql_query("UPDATE user_preferences SET `value` = NULL WHERE user = " . $userref . " AND parameter = 'colour_theme';");
            rs_setcookie("colour_theme", "",100, "/", "", substr($baseurl,0,5)=="https", true);
            exit("1");
            }
        }
    if(in_array("col-".$ctheme,$plugin_names))
        {
        // check that record exists for user
        if(empty($userpreferences))
            {
            // create a record
            sql_query("INSERT into user_preferences (user, parameter, `value`) VALUES (" . $userref . ", 'colour_theme', '" . escape_check(preg_replace('/^col-/', '', $ctheme)) . "');");
            rs_setcookie("colour_theme", escape_check(preg_replace("/^col-/","",$ctheme)),100, "/", "", substr($baseurl,0,5)=="https", true);
            exit("1");
            }
        else
            {
            sql_query("UPDATE user_preferences SET `value` = '" . escape_check(preg_replace('/^col-/', '', $ctheme)) . "' WHERE user = " . $userref . " AND parameter = 'colour_theme';");
            rs_setcookie("colour_theme", escape_check(preg_replace("/^col-/","",$ctheme)),100, "/", "", substr($baseurl,0,5)=="https", true);
            exit("1");
            }
        }

    exit("0");
    }

$enable_disable_options = array($lang['userpreference_disable_option'], $lang['userpreference_enable_option']);

include "../../include/header.php";
?>
<div class="BasicsBox"> 
    <h1><?php echo $lang["userpreferences"]?></h1>
    <p><?php echo $lang["modifyuserpreferencesintro"]?></p>
    
    <?php
    /* Display */
    $options_available = 0; # Increment this to prevent a "No options available" message

    /* User Colour Theme Selection */
    if((isset($userfixedtheme) && $userfixedtheme=="") && isset($userpreferences_plugins["colourtheme"]) && count($userpreferences_plugins["colourtheme"])>0)
        {
        ?>
        <h2><?php echo $lang['userpreference_colourtheme']; ?></h2>
        <div class="Question">
            <label for="">
                <?php echo $lang["userpreferencecolourtheme"]; ?>
            </label>
            <script>
                function updateColourTheme(theme) {
                    jQuery.post(
                        window.location,
                        {"colour_theme":theme,"quicksave":"true"},
                        function(data){
                            location.reload();
                        });
                }
            </script>
            <?php
            # If there are multiple options provide a radio button selector
            if(count($userpreferences_plugins["colourtheme"])>1)
                { ?>
                <table id="" class="radioOptionTable">
                    <tbody>
                        <tr>
                        <!-- Default option -->
                        <td valign="middle">
                            <input 
                                type="radio" 
                                name="defaulttheme" 
                                value="default" 
                                onChange="updateColourTheme('default');"
                                <?php
                                    if
                                    (
                                        (isset($userpreferences["colour_theme"]) && $userpreferences["colour_theme"]=="") 
                                        || 
                                        (!isset($userpreferences["colour_theme"]) && $defaulttheme=="")
                                    ) { echo "checked";}
                                ?>
                            />
                        </td>
                        <td align="left" valign="middle">
                            <label class="customFieldLabel" for="defaulttheme">
                                <?php echo $lang["default"];?>
                            </label>
                        </td>
                        <?php
                        foreach($userpreferences_plugins["colourtheme"] as $colourtheme)
                            { ?>
                            <td valign="middle">
                                <input 
                                    type="radio" 
                                    name="defaulttheme" 
                                    value="<?php echo preg_replace("/^col-/","",$colourtheme["name"]);?>" 
                                    onChange="updateColourTheme('<?php echo preg_replace("/^col-/","",$colourtheme["name"]);?>');"
                                    <?php
                                        if
                                        (
                                            (isset($userpreferences["colour_theme"]) && "col-".$userpreferences["colour_theme"]==$colourtheme["name"]) 
                                            || 
                                            (!isset($userpreferences["colour_theme"]) && $defaulttheme==$colourtheme["name"])
                                        ) { echo "checked";}
                                    ?>
                                />
                            </td>
                            <td align="left" valign="middle">
                                <label class="customFieldLabel" for="defaulttheme">
                                    <?php echo $colourtheme["name"];?>
                                </label>
                            </td>
                            <?php
                            }
                        ?>
                        </tr>
                    </tbody>
                </table>
                <?php
                }
            ?>
            <div class="clearerleft"> </div>
        </div>
        <?php
        $options_available++;
        }
    /* End User Colour Theme Selection */

    /* Default display if there are no options available */
    if($options_available == 0)
        { ?>
        <div class="FormError"><?php echo $lang["no-options-available"];?></div>
        <?php
        }
    ?>

<div class="CollapsibleSections">
    <?php
    // Result display section
    $page_def[] = config_add_html('<h2 class="CollapsibleSectionHead">' . $lang['resultsdisplay'] . '</h2><div id="UserPreferenceResultsDisplaySection" class="CollapsibleSection">');
    $page_def[] = config_add_single_select(
        'default_sort',
        $lang['userpreference_default_sort_label'],
        array(
            'relevance'  => $lang['relevance'],
            'resourceid' => $lang['resourceid'],
            'popularity' => $lang['popularity'],
            'rating'     => $lang['rating'],
            'date'       => $lang['date'],
            'colour'     => $lang['colour'],
        ),
        true,
        300,
        '',
        true
    );
    $page_def[] = config_add_single_select('default_perpage', $lang['userpreference_default_perpage_label'], array(24, 48, 72, 120, 240), false, 300, '', true);
    $page_def[] = config_add_single_select(
        'default_display',
        $lang['userpreference_default_display_label'],
        array(
            'smallthumbs' => $lang['smallthumbstitle'],
            'thumbs'      => $lang['largethumbstitle'],
            'xlthumbs'    => $lang['xlthumbstitle'],
            'list'        => $lang['listtitle']
        ),
        true,
        300,
        '',
        true
    );
    $page_def[] = config_add_boolean_select('use_checkboxes_for_selection', $lang['userpreference_use_checkboxes_for_selection_label'], $enable_disable_options, 300, '', true);
    $page_def[] = config_add_boolean_select('resource_view_modal', $lang['userpreference_resource_view_modal_label'], $enable_disable_options, 300, '', true);
    $page_def[] = config_add_html('</div>');
    ?>

    <?php
    // User interface section
    $page_def[] = config_add_html('<h2 class="CollapsibleSectionHead">' . $lang['userpreference_user_interface'] . '</h2><div id="UserPreferenceUserInterfaceSection" class="CollapsibleSection">');
    $page_def[] = config_add_single_select('thumbs_default', $lang['userpreference_thumbs_default_label'], array('show' => $lang['showthumbnails'], 'hide' => $lang['hidethumbnails']), true, 300, '', true);
    $page_def[] = config_add_boolean_select('basic_simple_search', $lang['userpreference_basic_simple_search_label'], $enable_disable_options, 300, '', true);
    $page_def[] = config_add_html('</div>');

    // Email section
    $page_def[] = config_add_html('<h2 class="CollapsibleSectionHead">' . $lang['email'] . '</h2><div id="UserPreferenceEmailSection" class="CollapsibleSection">');
    $page_def[] = config_add_boolean_select('cc_me', $lang['userpreference_cc_me_label'], $enable_disable_options, 300, '', true);
    $page_def[] = config_add_html('</div>');

    // Let plugins hook onto page definition and add their own configs if needed
    // or manipulate the list
    $plugin_specific_definition = hook('add_user_preference_page_def', '', array($page_def));
    if(is_array($plugin_specific_definition) && !empty($plugin_specific_definition))
        {
        $page_def = $plugin_specific_definition;
        }

    config_generate_html($page_def);
    ?>
</div>
    <script>
    registerCollapsibleSections();

    function AutoSaveConfigOption(option_name)
        {
        jQuery('#AutoSaveStatus-' + option_name).html('<?php echo $lang["saving"]; ?>');
        jQuery('#AutoSaveStatus-' + option_name).show();

        var option_value = null;
        var question_type = jQuery('#' + option_name).prop('nodeName');
        /* possible types:
        $('input') // selects all types of inputs
        $('input:checkbox') // selects checkboxes
        $('select') // selects select element
        $('input:radio') // selects radio inputs
        $('input[type="text"]') // selects text inputs
        */

        if(question_type.toLowerCase() === 'select')
            {
            option_value = jQuery('#' + option_name).val();
            }

        // save to user preferences table
        var post_url  = '<?php echo $baseurl; ?>/pages/ajax/user_preferences.php';
        var post_data = {
            ajax: true,
            autosave: true,
            autosave_option_name: option_name,
            autosave_option_value: option_value
        };

        jQuery.post(post_url, post_data, function(response) {

            if(response.success === true)
                {
                jQuery('#AutoSaveStatus-' + option_name).html('<?php echo $lang["saved"]; ?>');
                jQuery('#AutoSaveStatus-' + option_name).fadeOut('slow');
                }
            else if(response.success === false && response.message && response.message.length > 0)
                {
                jQuery('#AutoSaveStatus-' + option_name).html('<?php echo $lang["save-error"]; ?> ' + response.message);
                }
            else
                {
                jQuery('#AutoSaveStatus-' + option_name).html('<?php echo $lang["save-error"]; ?>');
                }

        }, 'json');

        return true;
        }
    </script>
</div>

<?php
include '../../include/footer.php';