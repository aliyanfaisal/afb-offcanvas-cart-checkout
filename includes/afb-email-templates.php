<?php
if (!defined('ABSPATH')) { exit; }

// Override WooCommerce core email templates with plugin versions
add_filter('woocommerce_locate_template', function($template, $template_name, $template_path) {
    // Only target the three core email templates we want to override
    $targets = [
        'emails/customer-completed-order.php',
        'emails/customer-processing-order.php',
        'emails/admin-new-order.php',
        'emails/customer-failed-order.php',
        'emails/customer-on-hold-order.php',
        'emails/customer-refunded-order.php',
        'emails/customer-invoice.php',
        'emails/admin-cancelled-order.php',
    ];

    if (in_array($template_name, $targets, true)) {
        $plugin_template = AFB_OFFCANVAS_DIR . 'templates/' . $template_name;
        if (file_exists($plugin_template)) {
            return $plugin_template;
        }
    }
    return $template;
}, 10, 3);