<?php
if (!defined('ABSPATH')) { exit; }

// Helper: whether email should hide aggregated shipping details
if (!function_exists('afb_is_hide_shipping_email_order')) {
    function afb_is_hide_shipping_email_order($order) {
        if (!$order || !is_a($order, 'WC_Order')) { return false; }
        $is_multiship_flag = (bool) $order->get_meta('afb_is_multiship');
        if (!$is_multiship_flag) { $is_multiship_flag = (bool) get_post_meta($order->get_id(), 'afb_is_multiship', true); }
        $delivery_option = (string) $order->get_meta('afb_delivery_option');
        if ($delivery_option === '') { $delivery_option = (string) get_post_meta($order->get_id(), 'afb_delivery_option', true); }
        return $is_multiship_flag || in_array($delivery_option, ['multiship', 'pickup'], true);
    }
}

// Hide Shipping section in modern Woo templates
add_filter('woocommerce_email_customer_details_show_shipping', function ($show, $order, $sent_to_admin, $plain_text, $email) {
    return afb_is_hide_shipping_email_order($order) ? false : $show;
}, 999, 5);

// Legacy fallback used by some themes/templates
add_filter('woocommerce_email_show_shipping_address', function ($show, $order) {
    return afb_is_hide_shipping_email_order($order) ? false : $show;
}, 999, 2);

// Extra CSS safety: hide shipping address column if a theme hardcodes it
add_filter('woocommerce_email_styles', function ($css, $email) {
    $order = null;
    if (is_object($email) && isset($email->object) && is_a($email->object, 'WC_Order')) {
        $order = $email->object;
    }
    if (!$order || !afb_is_hide_shipping_email_order($order)) { return $css; }
    $extra = '#addresses .shipping-address, .address.shipping-address, .shipping_address, td.shipping-address { display:none !important; }';
    return $css . "\n" . $extra;
}, 10, 2);

// Add a marker header to identify emails where shipping should be stripped in wp_mail
add_filter('woocommerce_email_headers', function ($headers, $email_id, $order, $email) {
    if (is_a($order, 'WC_Order') && afb_is_hide_shipping_email_order($order)) {
        if (is_array($headers)) {
            $headers[] = 'X-AFB-HideShipping: 1';
        } else {
            $headers = trim((string) $headers) . "\r\nX-AFB-HideShipping: 1";
        }
    }
    return $headers;
}, 10, 4);

// Strip shipping-related HTML from the final email message when marked for hiding
add_filter('wp_mail', function ($args) {
    $headers_str = '';
    if (isset($args['headers'])) {
        if (is_array($args['headers'])) {
            $headers_str = implode("\n", $args['headers']);
        } else {
            $headers_str = (string) $args['headers'];
        }
    }

    if (stripos($headers_str, 'X-AFB-Multiship: 1') !== false || stripos($headers_str, 'X-AFB-HideShipping: 1') !== false) {
        $message = isset($args['message']) ? (string) $args['message'] : '';

        // Remove <td> block that contains translated "Shipping address" (classic table layout)
        $shipping_label = trim(__('Shipping address', 'woocommerce'));
        $pattern_td = '/<td[^>]*>\s*<b[^>]*>\s*' . preg_quote($shipping_label, '/') . '[\s\S]*?<\/td>/si';
        $message = preg_replace($pattern_td, '', $message);

        // Variant: when the template uses class="address-title" for label
        if ($shipping_label !== '') {
            $pattern_td_title_class = '/<td[^>]*>\s*<b[^>]*class=["\']address-title["\'][^>]*>\s*' . preg_quote($shipping_label, '/') . '\s*<\/b>[\s\S]*?<\/td>/si';
            $message = preg_replace($pattern_td_title_class, '', $message);
        }

        // Remove div blocks that contain the shipping label (modern layouts)
        $pattern_div = '/<div[^>]*>\s*<strong[^>]*>\s*' . preg_quote($shipping_label, '/') . '\s*<\/strong>[\s\S]*?<\/div>/si';
        $message = preg_replace($pattern_div, '', $message);

        // Plain text fallback: remove any line containing "Shipping address"
        $pattern_text = '/^.*' . preg_quote($shipping_label, '/') . '.*$/mi';
        $message = preg_replace($pattern_text, '', $message);

        // Structural fallback: drop the second <td> in the #addresses table (typical shipping column)
        $pattern_struct = '/(<table[^>]*id=["\']addresses["\'][^>]*>[\s\S]*?<tr[^>]*>[\s\S]*?)(<td\b[\s\S]*?<\/td>\s*)(<td\b[\s\S]*?<\/td>)/i';
        $message = preg_replace($pattern_struct, '$1$2', $message);

        $args['message'] = $message;
    }

    return $args;
}, 10, 1);
