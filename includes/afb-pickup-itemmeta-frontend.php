<?php
if (!defined('ABSPATH')) { exit; }

// Show pickup store name under each line item in thank-you page and emails
add_action('woocommerce_order_item_meta_end_', function ($item_id, $item, $order, $plain_text) {
    if (!is_object($item) || (method_exists($item, 'get_type') && $item->get_type() !== 'line_item')) {
        return;
    }
    if (!is_object($order) || !is_a($order, 'WC_Order')) { return; }

    $delivery_option = (string) $order->get_meta('afb_delivery_option');
    if ($delivery_option === '') { $delivery_option = (string) get_post_meta($order->get_id(), 'afb_delivery_option', true); }
    if ($delivery_option !== 'pickup') { return; }

    $pickup_location = (string) $order->get_meta('_pickup_location', true);
    if ($pickup_location === '') { $pickup_location = (string) get_post_meta($order->get_id(), '_pickup_location', true); }
    if ($pickup_location === '') { return; }

    if ($plain_text) {
        $formatted = function_exists('afb_format_pickup_location') ? afb_format_pickup_location($pickup_location) : $pickup_location;
        echo "\n" . __('Pickup store', 'afb-offcanvas') . ': ' . $formatted . "\n";
        return;
    }

    echo '<div class="afb-pickup-store-display" style="margin-top:6px; display:flex; align-items:center; gap:6px;">'
       . '<span class="dashicons dashicons-store" aria-hidden="true"></span>'
       . '<span><strong>' . esc_html(__('Pickup store', 'afb-offcanvas')) . ':</strong> ' . esc_html( function_exists('afb_format_pickup_location') ? afb_format_pickup_location($pickup_location) : $pickup_location ) . '</span>'
       . '</div>';
}, 12, 4);