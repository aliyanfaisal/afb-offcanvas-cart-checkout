<?php
// Prevent direct access
if (!defined('ABSPATH')) { exit; }

// Hide raw item meta keys from the default admin item meta list
add_filter('woocommerce_hidden_order_itemmeta', function ($hidden) {
    if (!is_array($hidden)) { $hidden = []; }
    $hidden = array_merge($hidden, [
        'selected_address_key',
        'selected_address',
        'product_phone',
        'product_message',
    ]);
    return array_unique($hidden);
}, 10, 1);

// Render formatted address, phone, and message after the item meta in admin (Edit Order)
add_action('woocommerce_after_order_itemmeta', function ($item_id, $item, $product) {
    // Only for line items
    if (!is_object($item) || (method_exists($item, 'get_type') && $item->get_type() !== 'line_item')) {
        return;
    }

    // Safely read meta values
    $address_meta = method_exists($item, 'get_meta') ? $item->get_meta('selected_address', true) : '';
    $phone_meta   = method_exists($item, 'get_meta') ? (string) $item->get_meta('product_phone', true) : '';
    $message_meta = method_exists($item, 'get_meta') ? (string) $item->get_meta('product_message', true) : '';

    if (empty($phone_meta) && empty($message_meta) && (!is_array($address_meta) || empty($address_meta))) {
        return;
    }

    echo '<div class="afb-order-itemmeta-display" style="margin-top:6px;">';

    // Address: show compact vertical table (no alias fallback)
    if (is_array($address_meta) && !empty($address_meta)) {
        echo '<div style="margin-bottom:4px;"><span class="dashicons dashicons-location" aria-hidden="true"></span><strong>' . esc_html(__('Shipping address', 'woocommerce')) . '</strong></div>';
        // Build labels excluding first/last name
        $labels = [
            'shipping_first_name'    => __('First Name', 'woocommerce'),  'shipping_last_name'    => __('Last Name', 'woocommerce'), 'shipping_company'    => __('Company', 'woocommerce'),
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

    // Styled phone line with icon
    if ($phone_meta !== '') {
        echo '<div style="margin-top:8px; display:flex; align-items:center; gap:6px;">'
           . '<span class="dashicons dashicons-phone" aria-hidden="true"></span>'
           . '<span><strong>' . esc_html(__('Recipient’s Phone Number', 'woocommerce')) . ':</strong> ' . esc_html($phone_meta) . '</span>'
           . '</div>';
    }

    // Styled message line with icon
    if ($message_meta !== '') {
        echo '<div style="margin-top:6px; display:flex; align-items:center; gap:6px;">'
           . '<span class="dashicons dashicons-admin-comments" aria-hidden="true"></span>'
           . '<span><strong>' . esc_html(__('Gift Message', 'woocommerce')) . ':</strong> ' . nl2br(esc_html($message_meta)) . '</span>'
           . '</div>';
    }

    echo '</div>';
}, 10, 3);