<?php
include_once __DIR__ . '/../../../include/render_functions.php';

function HookRefineresultsSearchRender_search_actions_add_option()
    {
    global $baseurl_short, $lang, $k, $search, $parameters_string, $result, $collections;

    $options = '';
    $results = 0;

    if(is_array($result))
        {
        $results = count($result);
        }

    if(is_array($collections))
        {
        $results += count($collections);
        }

    # External sharing search support. Clear search drops back to the collection only search.
    $default_search = '';
    if($k != '' || ($k == '' && substr($search, 0, 1) == '!'))
        {
        $s = explode(' ', $search);
        $default_search = $s[0];
        }

    // Search within these results option
    if ($results > 1)
        {
        $options .= render_dropdown_option('search_within_results', $lang['refineresults']);
        }

    // Clear search terms option
    if($search != '')
        {
        $data_attribute['url'] = sprintf('%spages/search.php?search=%s%s',
            $baseurl_short,
            $default_search,
            $parameters_string
        );
        $options .= render_dropdown_option('clear_search_terms', $lang['clearsearch'], $data_attribute);
        }

    return $options;
    }


function HookRefineresultsSearchRender_actions_add_option_js_case()
    {
    $cases  = '';
    $cases .= sprintf('
        case "%s":
            jQuery("#RefineResults").slideToggle();
            jQuery("#refine_keywords").focus();

            break;
        ',
        'search_within_results'
    );

    return $cases;
    }