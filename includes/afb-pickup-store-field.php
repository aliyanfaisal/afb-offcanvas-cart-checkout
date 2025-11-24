<?php

add_action('woocommerce_checkout_update_order_meta', 'save_pickup_location_to_order', 10, 2);

// Also set pickup location on the WC_Order object at creation time
add_action('woocommerce_checkout_create_order', function ($order, $data) {
    if (!is_a($order, 'WC_Order')) { return; }
    $pickup_location = isset($_POST['pickup_location']) ? sanitize_text_field($_POST['pickup_location']) : '';
    if ($pickup_location !== '') {
        $order->update_meta_data('_pickup_location', $pickup_location);
    }

    $delivery_option = isset($_POST['afb_delivery_option']) ? sanitize_text_field($_POST['afb_delivery_option']) : '';
    if ($delivery_option !== '') {
        $order->update_meta_data('afb_delivery_option', $delivery_option);
    }
}, 10, 2);

function save_pickup_location_to_order($order_id, $data) {
    if (isset($_POST['pickup_location'])) {
        $pickup_location = sanitize_text_field($_POST['pickup_location']);
        update_post_meta($order_id, '_pickup_location', $pickup_location);
        $order = wc_get_order($order_id);
        if ($order && is_a($order, 'WC_Order')) {
            $order->update_meta_data('_pickup_location', $pickup_location);
            $order->save();
        }
    }
}

if (!function_exists('afb_format_pickup_location')) {
    function afb_format_pickup_location($raw) {
        $val = is_string($raw) ? trim($raw) : (string) $raw;
        if ($val === '') { return ''; }
        if (ctype_digit($val)) {
            $uid = (int) $val;
            if ($uid > 0) {
                $user = get_user_by('id', $uid);
                if ($user) {
                    $name = $user->display_name ? $user->display_name : $user->user_login;
                    $city = get_user_meta($uid, 'store_city', true);
                    return $city ? ($name . ' - ' . $city) : $name;
                }
            }
        }
        return $val;
    }
}

if (!function_exists('afb_get_store_info')) {
    function afb_get_store_info($raw) {
        $val = is_string($raw) ? trim($raw) : (string) $raw;
        $info = [ 'name' => '', 'address' => '', 'city' => '', 'phone' => '' ];
        if ($val === '') { return $info; }
        if (ctype_digit($val)) {
            $uid = (int) $val;
            if ($uid > 0) {
                $user = get_user_by('id', $uid);
                if ($user) {
                    $info['name']    = $user->display_name ? $user->display_name : $user->user_login;
                    $info['address'] = (string) get_user_meta($uid, 'store_address', true);
                    $info['city']    = (string) get_user_meta($uid, 'store_city', true);
                    $info['phone']   = (string) get_user_meta($uid, 'store_phone', true);
                }
            }
        } else {
            $info['name'] = $val;
        }
        return $info;
    }
}




add_action('woocommerce_admin_order_data_after_shipping_address', 'display_pickup_location_in_admin', 10, 1);

function display_pickup_location_in_admin($order) {
    $pickup_location = get_post_meta($order->get_id(), '_pickup_location', true);
    
    $is_flag = (bool) $order->get_meta('afb_is_multiship');
    if (!$is_flag) { $is_flag = (bool) get_post_meta($order->get_id(), 'afb_is_multiship', true); }
    $delivery_option = (string) $order->get_meta('afb_delivery_option');
    if ($delivery_option === '') { $delivery_option = (string) get_post_meta($order->get_id(), 'afb_delivery_option', true); }
    $is_multiship = ( $is_flag || $delivery_option === 'multiship');
    if (!$is_multiship) { return; }


    if (!empty($pickup_location) ) {
        echo '<div class="order-pickup-location">';
        echo '<h3>' . __('Pickup Location', 'your-textdomain') . '</h3>';
        echo '<p>' . esc_html( afb_format_pickup_location($pickup_location) ) . '</p>';
        echo '</div>';
        
        // Add some basic styling
        echo '<style>
            .order-pickup-location {
                margin-top: 20px;
                padding: 15px;
                background: #f5f5f5;
                border: 1px solid #ddd;
            }
        </style>';
    }
}