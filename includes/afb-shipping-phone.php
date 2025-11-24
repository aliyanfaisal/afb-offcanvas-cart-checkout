<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Save shipping phone to order meta during checkout
add_action('woocommerce_checkout_create_order', function ($order, $data) {
    if (isset($_POST['shipping_phone'])) {
        $phone = sanitize_text_field($_POST['shipping_phone']);
        if ($phone !== '') {
            if (method_exists($order, 'update_meta_data')) {
                $order->update_meta_data('_shipping_phone', $phone);
            } else {
                // Fallback for older WC versions
                update_post_meta($order->get_id(), '_shipping_phone', $phone);
            }
        }
    }
}, 10, 2);

// Display shipping phone under the admin shipping address section
add_action('woocommerce_admin_order_data_after_shipping_address', function ($order) {
    if (!$order) { return; }
    $phone = get_post_meta($order->get_id(), '_shipping_phone', true);
    if (!empty($phone)) {
        echo '<p><strong>' . esc_html__('Téléphone', 'afb-offcanvas') . ':</strong> ' . esc_html($phone) . '</p>';
    }
}, 10, 1);