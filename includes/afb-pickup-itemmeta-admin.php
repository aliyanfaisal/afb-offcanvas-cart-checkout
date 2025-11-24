<?php
if (!defined('ABSPATH')) { exit; }

// Show pickup store name under each line item in admin Edit Order screen
add_action('woocommerce_after_order_itemmeta_', function ($item_id, $item, $product) {
    // Only handle product line items
    if (!is_object($item) || (method_exists($item, 'get_type') && $item->get_type() !== 'line_item')) {
        return;
    }

    $order_id = method_exists($item, 'get_order_id') ? (int) $item->get_order_id() : 0;
    if (!$order_id) { return; }
    $order = wc_get_order($order_id);
    if (!$order || !is_a($order, 'WC_Order')) { return; }

    $delivery_option = (string) $order->get_meta('afb_delivery_option');
    if ($delivery_option === '') { $delivery_option = (string) get_post_meta($order->get_id(), 'afb_delivery_option', true); }
    if ($delivery_option !== 'pickup') { return; }

    $pickup_location = (string) $order->get_meta('_pickup_location', true);
    if ($pickup_location === '') { $pickup_location = (string) get_post_meta($order->get_id(), '_pickup_location', true); }
    if ($pickup_location === '') { return; }

    echo '<div class="afb-pickup-store-display" style="margin-top:6px; display:flex; align-items:center; gap:6px;">'
       . '<span class="dashicons dashicons-store" aria-hidden="true"></span>'
       . '<span><strong>' . esc_html(__('Pickup store', 'afb-offcanvas')) . ':</strong> ' . esc_html( function_exists('afb_format_pickup_location') ? afb_format_pickup_location($pickup_location) : $pickup_location ) . '</span>'
       . '</div>';
}, 12, 3);

// Hide the shipping address column on Edit Order when delivery is pickup
add_action('admin_head', function() {
    global $pagenow;
    if ($pagenow !== 'post.php') { return; }
    $post_id = isset($_GET['post']) ? absint($_GET['post']) : 0;
    if (!$post_id) { return; }
    $order = wc_get_order($post_id);
    if (!$order || !is_a($order, 'WC_Order')) { return; }

    $delivery_option = (string) $order->get_meta('afb_delivery_option');
    if ($delivery_option === '') { $delivery_option = (string) get_post_meta($order->get_id(), 'afb_delivery_option', true); }
    if ($delivery_option !== 'pickup') { return; }

    // Hide the shipping address column 
    echo '<style id="afb-hide-shipping-column-on-pickup">'
       . '#order_data > div.order_data_column_container > div:nth-child(3) > div.address { display:none !important; }'
       . '</style>';
});