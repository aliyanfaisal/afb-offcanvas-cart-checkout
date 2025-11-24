<?php

// Add reorder button to my orders
add_filter('woocommerce_my_account_my_orders_actions', 'add_reorder_button_to_orders', 10, 2);
function add_reorder_button_to_orders($actions, $order) {
    // Only show for completed and processing orders
    if (in_array($order->get_status(), array('completed', 'processing'))) {
        $actions['reorder'] = array(
            'url'  => wp_nonce_url(
                add_query_arg(array(
                    'reorder' => $order->get_id()
                ), wc_get_account_endpoint_url('orders')),
                'woocommerce-reorder',
                'reorder_nonce'
            ),
            'name' => __('Reorder', 'woocommerce'),
            'class' => 'button reorder'
        );
    }
    return $actions;
}

// Handle the reorder request
add_action('template_redirect', 'handle_reorder_request');
function handle_reorder_request() {
    // Check if this is a reorder request
    if (!isset($_GET['reorder']) || !isset($_GET['reorder_nonce'])) {
        return;
    }

    // Verify nonce
    if (!wp_verify_nonce($_GET['reorder_nonce'], 'woocommerce-reorder')) {
        wc_add_notice(__('Security check failed', 'woocommerce'), 'error');
        wp_safe_redirect(wc_get_account_endpoint_url('orders'));
        exit;
    }

    $order_id = absint($_GET['reorder']);
    
    // Check if user owns this order
    if (!current_user_can('view_order', $order_id)) {
        wc_add_notice(__('You cannot reorder this order', 'woocommerce'), 'error');
        wp_safe_redirect(wc_get_account_endpoint_url('orders'));
        exit;
    }

    $order = wc_get_order($order_id);
    
    if (!$order) {
        wc_add_notice(__('Order not found', 'woocommerce'), 'error');
        wp_safe_redirect(wc_get_account_endpoint_url('orders'));
        exit;
    }

    // Check if WooCommerce cart is available
    if (!WC()->cart) {
        wc_add_notice(__('Cart not available', 'woocommerce'), 'error');
        wp_safe_redirect(wc_get_account_endpoint_url('orders'));
        exit;
    }

    // Clear current cart first
    WC()->cart->empty_cart();
    
    $items_added = 0;
    $failed_items = array();

    // Add items to cart
    foreach ($order->get_items() as $item_id => $item) {
        try {
            $product_id = $item->get_product_id();
            $variation_id = $item->get_variation_id();
            $quantity = $item->get_quantity();
            
            // Get product
            $product = $variation_id ? wc_get_product($variation_id) : wc_get_product($product_id);
            
            if (!$product || !$product->is_purchasable()) {
                $failed_items[] = $product ? $product->get_name() : sprintf(__('Product #%d', 'woocommerce'), $product_id);
                continue;
            }
            
            // Handle stock
            if (!$product->has_enough_stock($quantity)) {
                $available = $product->get_stock_quantity();
                if ($available > 0) {
                    $quantity = $available;
                } else {
                    $failed_items[] = $product->get_name();
                    continue;
                }
            }
            
            // Handle variations
            $variation = array();
            if ($variation_id) {
                $variation = wc_get_product_variation_attributes($variation_id);
            }
            
            // Add to cart
            if (WC()->cart->add_to_cart($product_id, $quantity, $variation_id, $variation)) {
                $items_added++;
            } else {
                $failed_items[] = $product->get_name();
            }
            
        } catch (Exception $e) {
            $failed_items[] = isset($product) ? $product->get_name() : sprintf(__('Product #%d', 'woocommerce'), $product_id);
        }
    }

    // Add notices
    if ($items_added > 0) {
        wc_add_notice(sprintf(__('%d items from order #%s added to cart.', 'woocommerce'), $items_added, $order->get_order_number()), 'success');
    }
    
    if (!empty($failed_items)) {
        wc_add_notice(sprintf(__('Could not add these items: %s', 'woocommerce'), implode(', ', $failed_items)), 'error');
    }

    // Redirect to cart
//     wp_safe_redirect(wc_get_cart_url());
	 wp_safe_redirect('/cart');
    exit;
}

// Add simple CSS for the button
add_action('wp_head', 'add_reorder_button_css');
function add_reorder_button_css() {
    if (is_account_page()) {
        ?>
        <style>
        .reorder {
            background-color: #0073aa !important;
            color: white !important;
            padding: 6px 12px !important;
            border-radius: 3px !important;
            text-decoration: none !important;
            font-size: 12px !important;
            display: inline-block !important;
        }
        .reorder:hover {
            background-color: #005a87 !important;
            color: white !important;
        }
        </style>
        <?php
    }
}