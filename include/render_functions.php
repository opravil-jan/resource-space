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