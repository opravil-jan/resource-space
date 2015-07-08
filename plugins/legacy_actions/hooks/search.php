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