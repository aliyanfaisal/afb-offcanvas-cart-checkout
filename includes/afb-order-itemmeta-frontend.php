<?php
if (!defined('ABSPATH')) { exit; }

// Hide product message, phone, and selected address from formatted item meta on thank-you page and emails
add_filter('woocommerce_order_item_get_formatted_meta_data', function ($formatted_meta, $item) {
    $hidden_keys = ['selected_address_key', 'selected_address', 'product_phone', 'product_message', 'thwma_order_status', 'thwma_shipping_status', 'shipping_status', 'order_status'];
    if (!is_array($formatted_meta)) { return $formatted_meta; }

    $filtered = [];
    foreach ($formatted_meta as $meta) {
        $key = null;
        if (is_object($meta)) {
            if (isset($meta->key)) {
                $key = $meta->key;
            } elseif (isset($meta->meta) && is_object($meta->meta) && method_exists($meta->meta, 'get_data')) {
                $data = $meta->meta->get_data();
                $key = isset($data['key']) ? $data['key'] : null;
            }
        } elseif (is_array($meta)) {
            $key = isset($meta['key']) ? $meta['key'] : null;
        }
        $is_thwma_status = (is_string($key) && strpos($key, 'thwma_') === 0 && strpos($key, 'status') !== false);
        if ($key === null || (!in_array($key, $hidden_keys, true) && !$is_thwma_status)) {
            $filtered[] = $meta;
        }
    }
    return $filtered;
}, 10, 2);

// Render formatted address, phone, and message after item meta on thank-you page and emails
add_action('woocommerce_order_item_meta_end', function ($item_id, $item, $order, $plain_text) {
    if (!is_object($item) || (method_exists($item, 'get_type') && $item->get_type() !== 'line_item')) {
        return;
    }

    // Show addresses under items ONLY when order is multiship
    $is_multiship = false;
    if (is_object($order) && method_exists($order, 'get_meta')) {
        $is_multiship = (bool) $order->get_meta('afb_is_multiship') || ((string) $order->get_meta('afb_delivery_option') === 'multiship');
        if (!$is_multiship) {
            $is_multiship = (bool) get_post_meta($order->get_id(), 'afb_is_multiship', true) || ((string) get_post_meta($order->get_id(), 'afb_delivery_option', true) === 'multiship');
        }
    }

    if (!$is_multiship) { return; }

    $address_meta = method_exists($item, 'get_meta') ? $item->get_meta('selected_address', true) : '';
    $phone_meta   = method_exists($item, 'get_meta') ? (string) $item->get_meta('product_phone', true) : '';
    $message_meta = method_exists($item, 'get_meta') ? (string) $item->get_meta('product_message', true) : '';

    if (empty($phone_meta) && empty($message_meta) && (!is_array($address_meta) || empty($address_meta))) {
        return;
    }

    // Plain text emails output
    if ($plain_text) {
        if (is_array($address_meta) && !empty($address_meta)) {
            $labels = [
                'shipping_first_name'    => __('First Name', 'woocommerce'),
                'shipping_last_name'    => __('Last Name', 'woocommerce'),
                'shipping_company'    => __('Company', 'woocommerce'),
                'shipping_address_1'  => __('Address line 1', 'woocommerce'),
                'shipping_address_2'  => __('Address line 2', 'woocommerce'),
                'shipping_city'       => __('City', 'woocommerce'),
                'shipping_state'      => __('State', 'woocommerce'),
                'shipping_postcode'   => __('Postcode', 'woocommerce'),
                'shipping_country'    => __('Country', 'woocommerce'),
            ];
            $parts = [];
            foreach ($labels as $key => $label) {
                $val = isset($address_meta[$key]) ? trim((string) $address_meta[$key]) : '';
                if ($val !== '') {
                    $parts[] = wp_strip_all_tags($label) . ': ' . $val;
                }
            }
            if (!empty($parts)) {
                echo "\n" . __('Recipient Address', 'afb-offcanvas') . ': ' . implode(', ', $parts);
            }
        }
        if ($phone_meta !== '') {
            echo "\n" . __('Recipient’s Phone Number', 'afb-offcanvas') . ': ' . $phone_meta;
        }
        if ($message_meta !== '') {
            echo "\n" . __('Gift Message', 'afb-offcanvas') . ': ' . $message_meta;
        }
        echo "\n";
        return;
    }

    // HTML output (thank-you page / HTML emails)
    echo '<div class="afb-order-itemmeta-display" style="margin-top:6px;">';

    if (is_array($address_meta) && !empty($address_meta)) {
        echo '<div style="margin-bottom:4px;"><strong>' . esc_html(__('Recipient Address', 'afb-offcanvas')) . '</strong></div>';
        $labels = [
            'shipping_first_name'    => __('First Name', 'woocommerce'),
            'shipping_last_name'    => __('Last Name', 'woocommerce'),
            'shipping_company'    => __('Company', 'woocommerce'),
            'shipping_address_1'  => __('Address line 1', 'woocommerce'),
            'shipping_address_2'  => __('Address line 2', 'woocommerce'),
            'shipping_city'       => __('City', 'woocommerce'),
            'shipping_state'      => __('State', 'woocommerce'),
            'shipping_postcode'   => __('Postcode', 'woocommerce'),
            'shipping_country'    => __('Country', 'woocommerce'),
        ];
        echo '<table class="afb-mini-address" style="margin-top:4px; border-collapse:collapse; font-size:12px;">';
        echo '<tbody>';
        foreach ($labels as $key => $label) {
            $val = isset($address_meta[$key]) ? trim((string) $address_meta[$key]) : '';
            if ($val !== '') {
                echo '<tr>'
                   . '<th style="padding:4px 6px; text-align:left; color:#444; font-weight:600; min-width:120px;">' . esc_html($label) . '</th>'
                   . '<td style="padding:4px 6px;">' . esc_html($val) . '</td>'
                   . '</tr>';
            }
        }
        echo '</tbody>';
        echo '</table>';
    }

    // Use table rows for recipient phone and gift message
    if ($phone_meta !== '' || $message_meta !== '') {
        echo '<table class="afb-mini-contact" style="margin-top:8px; border-collapse:collapse; font-size:12px;">';
        echo '<tbody>';
        if ($phone_meta !== '') {
            echo '<tr>'
               . '<th style="padding:4px 6px; text-align:left; color:#444; font-weight:600; min-width:120px;">' . esc_html(__('Recipient’s Phone Number', 'afb-offcanvas')) . '</th>'
               . '<td style="padding:4px 6px;">' . esc_html($phone_meta) . '</td>'
               . '</tr>';
        }
        if ($message_meta !== '') {
            echo '<tr>'
               . '<th style="padding:4px 6px; text-align:left; color:#444; font-weight:600; min-width:120px;">' . esc_html(__('Gift Message', 'afb-offcanvas')) . '</th>'
               . '<td style="padding:4px 6px;">' . nl2br(esc_html($message_meta)) . '</td>'
               . '</tr>';
        }
        echo '</tbody>';
        echo '</table>';
    }

    echo '</div>';
}, 10, 4);

// Helper: detect multiship orders
$afb_is_multiship_order = function ($order) {
    if (!$order) { return false; }
    $is_flag = (bool) $order->get_meta('afb_is_multiship');
    if (!$is_flag) { $is_flag = (bool) get_post_meta($order->get_id(), 'afb_is_multiship', true); }
    $delivery_option = (string) $order->get_meta('afb_delivery_option');
    if ($delivery_option === '') { $delivery_option = (string) get_post_meta($order->get_id(), 'afb_delivery_option', true); }
    $is_multiship_option = ($delivery_option === 'multiship');
    return ($is_flag || $is_multiship_option);
};

// Hide shipping column and order-actions on thank you page when multiship
add_action('woocommerce_thankyou', function ($order_id) use ($afb_is_multiship_order) {
    $order = wc_get_order($order_id);
    if (!$order) { return; }
    $delivery_option = (string) $order->get_meta('afb_delivery_option');
    if ($delivery_option === '') { $delivery_option = (string) get_post_meta($order->get_id(), 'afb_delivery_option', true); }
    $is_multiship = $afb_is_multiship_order($order);
    $is_pickup = ($delivery_option === 'pickup');
    if (!$is_multiship && !$is_pickup) { return; }
    echo '<style>
    .woocommerce-columns--addresses .woocommerce-column--shipping-address { display: none !important; }
    table.shop_table.order_details .order-actions, .order-actions, .woocommerce-orders-table__cell-order-actions { display: none !important; }
    table.shop_table.order_details tfoot .shipping { display: none !important; }
    .woocommerce-order-overview__shipping { display: none !important; }
    ' . ($is_pickup ? '.woocommerce-customer-details > address { display:none !important; }' : '') . '
    </style>';
}, 5);

// Hide shipping column and order-actions on view order page when multiship
add_action('woocommerce_view_order', function ($order_id) use ($afb_is_multiship_order) {
    $order = wc_get_order($order_id);
    if (!$order) { return; }
    $delivery_option = (string) $order->get_meta('afb_delivery_option');
    if ($delivery_option === '') { $delivery_option = (string) get_post_meta($order->get_id(), 'afb_delivery_option', true); }
    $is_multiship = $afb_is_multiship_order($order);
    $is_pickup = ($delivery_option === 'pickup');
    if (!$is_multiship && !$is_pickup) { return; }
    echo '<style>
    .woocommerce-columns--addresses .woocommerce-column--shipping-address { display: none !important; }
    table.shop_table.order_details .order-actions, .order-actions, .woocommerce-orders-table__cell-order-actions { display: none !important; }
    table.shop_table.order_details tfoot .shipping { display: none !important; }
    .woocommerce-order-overview__shipping { display: none !important; }
    ' . ($is_pickup ? '.woocommerce-customer-details > address { display:none !important; }' : '') . '
    </style>';
}, 5);

// Append email CSS to hide shipping address when multiship
add_filter('woocommerce_email_styles', function ($css, $email) use ($afb_is_multiship_order) {
    $order = null;
    if (is_object($email) && isset($email->object) && is_a($email->object, 'WC_Order')) {
        $order = $email->object;
    }
    if (!$order || !$afb_is_multiship_order($order)) { return $css; }

    $extra = "#addresses .shipping-address, .address.shipping-address, .shipping_address, td.shipping-address { display:none !important; }";
    return $css . "\n" . $extra;
}, 10, 2);

// Programmatically hide the Shipping address section in emails when multiship (modern templates)
add_filter('woocommerce_email_customer_details_show_shipping', function ($show, $order, $sent_to_admin, $plain_text, $email) use ($afb_is_multiship_order) {
    if ($afb_is_multiship_order($order)) { return false; }
    return $show;
}, 999, 5);

// Legacy fallback: some themes/templates use older filter name
add_filter('woocommerce_email_show_shipping_address', function ($show, $order) use ($afb_is_multiship_order) {
    if ($afb_is_multiship_order($order)) { return false; }
    return $show;
}, 999, 2);








// Pickup details section next to Billing Address on thank-you/view-order pages
add_action('woocommerce_order_details_after_customer_details', function ($order) {
    if (!is_object($order) || !is_a($order, 'WC_Order')) { return; }
    $delivery_option = (string) $order->get_meta('afb_delivery_option');
    if ($delivery_option === '') { $delivery_option = (string) get_post_meta($order->get_id(), 'afb_delivery_option', true); }
    if ($delivery_option !== 'pickup') { return; }
    $pickup_location = (string) $order->get_meta('_pickup_location', true);
    if ($pickup_location === '') { $pickup_location = (string) get_post_meta($order->get_id(), '_pickup_location', true); }
    if ($pickup_location === '') { return; }

    $billing_address_html = $order->get_formatted_billing_address();
    $billing_phone = $order->get_billing_phone();
    $billing_email = $order->get_billing_email();

    echo '<div style="margin-top:12px;">';
    echo '<div style="display:grid; grid-template-columns: 1fr 1fr; gap: 12px; align-items:start">';
    echo '<div>';
    echo '<b>' . esc_html(__('Billing address', 'woocommerce')) . '</b>';
    echo '<address style="padding:8px 0; word-break:break-all">' . wp_kses_post($billing_address_html);
    if ($billing_phone) { echo '<br><a href="tel:' . esc_attr($billing_phone) . '" target="_blank" style="color:#b7c4ca; font-weight:normal; text-decoration:underline">' . esc_html($billing_phone) . '</a>'; }
    if ($billing_email) { echo '<br><a href="mailto:' . esc_attr($billing_email) . '" target="_blank" style="color:#b7c4ca; font-weight:normal; text-decoration:underline">' . esc_html($billing_email) . '</a>'; }
    echo '</address>';
    echo '</div>';
    echo '<div>';
    echo '<b>' . esc_html__('In-Store Pickup Info', 'afb-offcanvas') . '</b>';
    $info = function_exists('afb_get_store_info') ? afb_get_store_info($pickup_location) : null;
    echo '<div style="padding:8px 0;">';
    if ($info && ($info['address'] || $info['city'] || $info['phone'])) {
        echo '<p style="margin:0;">' . esc_html( $info['name'] ) . '</p>';
        if ($info['address']) { echo '<p style="margin:0;">' . esc_html( $info['address'] ) . '</p>'; }
        if ($info['city'])    { echo '<p style="margin:0;">' . esc_html( $info['city'] ) . '</p>'; }
        if ($info['phone'])   { echo '<p style="margin:0;">' . esc_html( $info['phone'] ) . '</p>'; }
    } else {
        echo '<p style="margin:0;">' . esc_html( function_exists('afb_format_pickup_location') ? afb_format_pickup_location($pickup_location) : $pickup_location ) . '</p>';
    }
    echo '</div>';
    echo '</div>';
    echo '</div>';
    echo '</div>';
}, 15);

// Pickup details section in emails within customer details block
add_action('woocommerce_email_customer_details', function ($order, $sent_to_admin, $plain_text, $email) {
    if (!is_object($order) || !is_a($order, 'WC_Order')) { return; }
    $delivery_option = (string) $order->get_meta('afb_delivery_option');
    if ($delivery_option === '') { $delivery_option = (string) get_post_meta($order->get_id(), 'afb_delivery_option', true); }
    $pickup_location = (string) $order->get_meta('_pickup_location', true);
    if ($pickup_location === '') { $pickup_location = (string) get_post_meta($order->get_id(), '_pickup_location', true); }
    if ($pickup_location === '' && $delivery_option !== 'pickup') { return; }
    if ($pickup_location === '') { return; }

    if ($plain_text) {
        $info = function_exists('afb_get_store_info') ? afb_get_store_info($pickup_location) : null;
        if ($info && ($info['address'] || $info['city'] || $info['phone'])) {
            echo "\n" . __('In-Store Pickup Info', 'afb-offcanvas') . ":\n";
            echo ($info['name'] ? $info['name'] . "\n" : '');
            echo ($info['address'] ? $info['address'] . "\n" : '');
            echo ($info['city'] ? $info['city'] . "\n" : '');
            echo ($info['phone'] ? $info['phone'] . "\n" : '');
        } else {
            $formatted = function_exists('afb_format_pickup_location') ? afb_format_pickup_location($pickup_location) : $pickup_location;
            echo "\n" . __('In-Store Pickup Info', 'afb-offcanvas') . ': ' . $formatted . "\n";
        }
        return;
    }

    echo '<div class="afb-email-pickup-details" style="margin-top:12px;">';
    echo '<h3 style="font-size:16px; margin:0 0 6px;">' . esc_html__('In-Store Pickup Info', 'afb-offcanvas') . '</h3>';
    $info = function_exists('afb_get_store_info') ? afb_get_store_info($pickup_location) : null;
    if ($info && ($info['address'] || $info['city'] || $info['phone'])) {
        echo '<p style="margin:0;">' . esc_html( $info['name'] ) . '</p>';
        if ($info['address']) { echo '<p style="margin:0;">' . esc_html( $info['address'] ) . '</p>'; }
        if ($info['city'])    { echo '<p style="margin:0;">' . esc_html( $info['city'] ) . '</p>'; }
        if ($info['phone'])   { echo '<p style="margin:0;">' . esc_html( $info['phone'] ) . '</p>'; }
    } else {
        echo '<p style="margin:0;">' . esc_html( function_exists('afb_format_pickup_location') ? afb_format_pickup_location($pickup_location) : $pickup_location ) . '</p>';
    }
    echo '</div>';
}, 15, 4);

// Helper: get current order from request context (thank-you / view-order / order-pay)
if (!function_exists('afb_get_current_order_from_request')) {
    function afb_get_current_order_from_request() {
        // Prefer global $order if available
        if (isset($GLOBALS['order']) && is_a($GLOBALS['order'], 'WC_Order')) {
            return $GLOBALS['order'];
        }

        // Try order key from request
        $key = isset($_GET['key']) ? wc_clean(wp_unslash($_GET['key'])) : '';
        if ($key) {
            $order_id = wc_get_order_id_by_order_key($key);
            if ($order_id) {
                $order = wc_get_order($order_id);
                if ($order) { return $order; }
            }
        }

        // Try common order endpoints
        $order_id = 0;
        $candidates = ['order-received', 'view-order', 'order-pay'];
        foreach ($candidates as $var) {
            $val = get_query_var($var);
            if ($val) { $order_id = absint($val); break; }
        }
        if (!$order_id && isset($_GET['order'])) {
            $order_id = absint($_GET['order']);
        }
        if ($order_id) {
            $order = wc_get_order($order_id);
            if ($order) { return $order; }
        }

        return null;
    }
}

// Globally suppress aggregate shipping address block when order is multiship
add_filter('woocommerce_order_needs_shipping_address', function ($needs_address, $hide) use ($afb_is_multiship_order) {
    // In emails, rely on dedicated email filters for reliability
    if (doing_action('woocommerce_email_customer_details') || doing_action('woocommerce_email_order_details')) {
        return $needs_address;
    }

    $order = afb_get_current_order_from_request();
    if ($order && $afb_is_multiship_order($order)) {
        return false;
    }
    return $needs_address;
}, 999, 2);



// Fix unit price display in order details/emails: ensure "qty × unit" uses per-unit price
add_filter('woocommerce_order_item_subtotal', function($subtotal, $item, $order) {
    // Only adjust for product line items
    if (!is_object($item) || (method_exists($item, 'get_type') && $item->get_type() !== 'line_item')) {
        return $subtotal;
    }
    $qty = (int) (method_exists($item, 'get_quantity') ? $item->get_quantity() : 1);
    if ($qty <= 0) { $qty = 1; }

    // Use recorded totals on the item to avoid product price drift
    $line_total = (float) (method_exists($item, 'get_total') ? $item->get_total() : 0.0);
    $line_tax   = (float) (method_exists($item, 'get_total_tax') ? $item->get_total_tax() : 0.0);

    // Fallback: if line_total looks wrong, prefer subtotal values
    if ($line_total <= 0 && method_exists($item, 'get_subtotal')) {
        $line_total = (float) $item->get_subtotal();
        $line_tax   = (float) (method_exists($item, 'get_subtotal_tax') ? $item->get_subtotal_tax() : 0.0);
    }

    // Decide display tax inclusion roughly according to store setting
    $include_tax = (bool) (function_exists('wc_tax_enabled') && wc_tax_enabled() && function_exists('wc_prices_include_tax') && wc_prices_include_tax());

    // Get per-unit price using WooCommerce helpers when available
    if (is_object($order) && method_exists($order, 'get_item_total')) {
        $unit = (float) $order->get_item_total($item, $include_tax, true);
    } else {
        $unit = $include_tax ? ($line_total + $line_tax) / $qty : $line_total / $qty;
    }
    if (!is_finite($unit) || $unit < 0) { $unit = 0; }

    // Format with order currency
    $currency = is_object($order) && method_exists($order, 'get_currency') ? $order->get_currency() : '';
    $unit_html = function_exists('wc_price') ? wc_price($unit, $currency ? ['currency' => $currency] : []) : number_format($unit, 2);

    // Return "qty x unit" string to avoid accidental use of line totals
    return sprintf('%d x %s', $qty, $unit_html);
}, 9999, 3);

// Disabled: prevent duplicate product image in order items/emails
/*
// Add product title next to image in emails order item details
add_filter('woocommerce_order_item_name', function ($item_name, $item, $is_visible) {
    // Only for product line items
    if (!is_object($item) || (method_exists($item, 'get_type') && $item->get_type() !== 'line_item')) {
        return $item_name;
    }

    // Get product image
    $product = method_exists($item, 'get_product') ? $item->get_product() : null;
    $image_html = '';
    if ($product && method_exists($product, 'get_image')) {
        // Small thumbnail with email-friendly inline styles
        $image_html = $product->get_image('thumbnail', [
            'style' => 'width:40px;height:auto;margin-right:8px;vertical-align:middle;border:0;outline:none;text-decoration:none;'
        ]);
    }

    if ($image_html === '') {
        return $item_name;
    }

    // Place title next to thumbnail
    return $image_html . '<span style="vertical-align:middle;display:inline-block;">' . $item_name . '</span>';
}, 12, 3);
*/
