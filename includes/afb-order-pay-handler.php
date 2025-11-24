<?php
/**
 * AFB Order Pay Handler
 * Handles pricing adjustments on order-pay pages based on delivery options
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Modify order totals on order-pay page based on delivery options
 */
function afb_modify_order_pay_totals($order) {
    // Only apply on order-pay pages
    if (!is_wc_endpoint_url('order-pay')) {
        return;
    }
    
    // Get delivery option from order meta
    $delivery_option = get_post_meta($order->get_id(), 'afb_delivery_option', true);
    
    // Get multiship data if available
    $is_multiship = get_post_meta($order->get_id(), 'afb_is_multiship', true);
    $multiship_data = get_post_meta($order->get_id(), 'afb_multiship_data', true);
    
    if ($delivery_option === 'pickup') {
        // For pickup orders, remove shipping costs
        afb_apply_pickup_pricing($order);
    } elseif ($is_multiship && $multiship_data) {
        // For multiship orders, apply multiship pricing
        afb_apply_multiship_pricing($order, $multiship_data);
    }
}

/**
 * Apply pickup pricing (free shipping) to order
 */
function afb_apply_pickup_pricing($order) {
    // Remove shipping costs from order
    $order->set_shipping_total(0);
    $order->set_shipping_tax(0);
    
    // Remove shipping items to ensure no shipping tax is calculated
    $shipping_items = $order->get_items('shipping');
    foreach ($shipping_items as $item_id => $shipping_item) {
        $order->remove_item($item_id);
    }
    
    // Force recalculate totals without shipping
    $order->calculate_totals(false);
    
    // Save the order to persist changes
    $order->save();
}

/**
 * Apply multiship pricing to order
 */
function afb_apply_multiship_pricing($order, $multiship_data) {
    if (!is_array($multiship_data)) {
        return;
    }
    
    // Resolve target totals
    $target_shipping_total = isset($multiship_data['multiplied_shipping']) ? (float) wc_format_decimal($multiship_data['multiplied_shipping']) : 0.0;
    $target_shipping_tax   = isset($multiship_data['multiplied_shipping_tax']) ? (float) wc_format_decimal($multiship_data['multiplied_shipping_tax']) : 0.0;
    $original_shipping_tax = isset($multiship_data['original_shipping_tax']) ? (float) wc_format_decimal($multiship_data['original_shipping_tax']) : 0.0;

    // Apply multiship shipping costs at order level
    $order->set_shipping_total($target_shipping_total);
    $order->set_shipping_tax($target_shipping_tax);

    // Distribute multiplied shipping across shipping items on order-pay
    $shipping_items = $order->get_items('shipping');
    $count = count($shipping_items);
    $per_item_total = ($count > 0) ? (float) wc_format_decimal($target_shipping_total / $count) : 0.0;
    $per_item_tax   = ($count > 0) ? (float) wc_format_decimal($target_shipping_tax / $count) : 0.0;
    foreach ($shipping_items as $item_id => $item) {
        // Set per-item portion on each shipping item
        $item->set_total($per_item_total);

        // Scale existing tax distribution to match per-item shipping tax
        $taxes = $item->get_taxes();
        $base_sum = 0.0;
        if (is_array($taxes) && !empty($taxes['total'])) {
            foreach ($taxes['total'] as $amt) { $base_sum += (float) $amt; }
        }
        if ($base_sum > 0.0 && $per_item_tax > 0.0 && is_array($taxes) && !empty($taxes['total'])) {
            $ratio = $per_item_tax / $base_sum;
            $new_total = [];
            foreach ($taxes['total'] as $rate_id => $amount) {
                $new_total[$rate_id] = (float) wc_format_decimal(((float) $amount) * $ratio);
            }
            $new_subtotal = [];
            if (!empty($taxes['subtotal'])) {
                foreach ($taxes['subtotal'] as $rate_id => $amount) {
                    $new_subtotal[$rate_id] = (float) wc_format_decimal(((float) $amount) * $ratio);
                }
            } else {
                $new_subtotal = $new_total;
            }
            $item->set_taxes(['total' => $new_total, 'subtotal' => $new_subtotal]);
        } else {
            $item->set_taxes(['total' => [], 'subtotal' => []]);
        }

        $item->save();
    }

    // Recalculate and persist
    $order->calculate_totals(false);
    $order->save();
}

/**
 * Hook to modify order before display on order-pay page
 */
function afb_before_order_pay_display() {
    if (is_wc_endpoint_url('order-pay')) {
        // Clear the user's cart upon visiting the order-pay page
        if (function_exists('WC') && WC()->cart) {
            WC()->cart->empty_cart();
        }

        global $wp;
        
        // Get order ID from URL
        $order_id = absint($wp->query_vars['order-pay']);
        
        if ($order_id) {
            $order = wc_get_order($order_id);
            if ($order) {
                afb_modify_order_pay_totals($order);
            }
        }
    }
}
add_action('wp', 'afb_before_order_pay_display', 20);

/**
 * Filter order item totals display on order-pay page
 */
function afb_filter_order_pay_item_totals($total_rows, $order, $tax_display) {
    // Only apply on order-pay pages
    if (!is_wc_endpoint_url('order-pay')) {
        return $total_rows;
    }
    
    // Get delivery option from order meta
    $delivery_option = get_post_meta($order->get_id(), 'afb_delivery_option', true);
    $is_multiship = get_post_meta($order->get_id(), 'afb_is_multiship', true);
    $multiship_data = get_post_meta($order->get_id(), 'afb_multiship_data', true);
    
    if ($delivery_option === 'pickup') {
        // Modify shipping row for pickup
        if (isset($total_rows['shipping'])) {
            $total_rows['shipping']['value'] = __('Gratuit', 'afb-offcanvas');
        }
        
        // For pickup orders, calculate total as subtotal + product taxes only
        $new_total = $order->get_subtotal();
        
        // Add product taxes only (no shipping tax)
        if (wc_tax_enabled()) {
            // Get all tax items and exclude shipping tax
            $tax_items = $order->get_items('tax');
            $product_tax_total = 0;
            
            foreach ($tax_items as $tax_item) {
                $tax_data = $tax_item->get_data();
                // Only add non-shipping tax amounts
                if (!empty($tax_data['tax_total'])) {
                    $product_tax_total += $tax_data['tax_total'];
                }
            }
            
            $new_total += $product_tax_total;
        }
        
        $total_rows['order_total']['value'] = wc_price($new_total);
        
    } elseif ($is_multiship && $multiship_data && is_array($multiship_data)) {
        // Modify shipping and total for multiship
        if (isset($multiship_data['multiplied_shipping']) && isset($total_rows['shipping'])) {
            $total_rows['shipping']['value'] = wc_price($multiship_data['multiplied_shipping']);
        }
        
        if (isset($multiship_data['multiplied_shipping_tax']) && isset($total_rows['tax'])) {
            $total_rows['tax']['value'] = wc_price($multiship_data['multiplied_shipping_tax']);
        }
        
        // Recalculate total with multiship adjustments
        $new_total = $order->get_subtotal();
        
        if (isset($multiship_data['multiplied_shipping'])) {
            $new_total += $multiship_data['multiplied_shipping'];
        }
        
        if (wc_tax_enabled() && !$order->get_prices_include_tax()) {
            $new_total += $order->get_total_tax() - $order->get_shipping_tax();
            if (isset($multiship_data['multiplied_shipping_tax'])) {
                $new_total += $multiship_data['multiplied_shipping_tax'];
            }
        }
        
        $total_rows['order_total']['value'] = wc_price($new_total);
    }
    
    return $total_rows;
}
add_filter('woocommerce_get_order_item_totals', 'afb_filter_order_pay_item_totals', 10, 3);

/**
 * Save multiship data to order meta during checkout
 */
function afb_save_multiship_data_to_order($order_id) {
    // Check if cart is in multiship mode
    $is_cart_split = WC()->session->get('afb_cart_split', false);
    $multiship_data = WC()->session->get('afb_multiship_data', null);
    
    if ($is_cart_split && $multiship_data) {
        update_post_meta($order_id, 'afb_is_multiship', true);
        update_post_meta($order_id, 'afb_multiship_data', $multiship_data);
    }
}
add_action('woocommerce_checkout_update_order_meta', 'afb_save_multiship_data_to_order');

/**
 * Display delivery and multiship information in admin order details
 */
// function afb_display_order_pay_info_in_admin($order) {
//     $delivery_option = get_post_meta($order->get_id(), 'afb_delivery_option', true);
//     $is_multiship = get_post_meta($order->get_id(), 'afb_is_multiship', true);
    
//     if ($delivery_option) {
//         $delivery_labels = array(
//             'pickup' => __('Retrait en magasin', 'afb-offcanvas'),
//             'delivery' => __('Livraison à domicile', 'afb-offcanvas'),
//             'multiship' => __('Livraison multiple', 'afb-offcanvas')
//         );
        
//         $label = isset($delivery_labels[$delivery_option]) ? $delivery_labels[$delivery_option] : $delivery_option;
        
//         echo '<p><strong>' . __('Option de livraison:', 'afb-offcanvas') . '</strong> ' . esc_html($label) . '</p>';
//     }
    
//     if ($is_multiship) {
//         echo '<p><strong>' . __('Commande multiship:', 'afb-offcanvas') . '</strong> ' . __('Oui', 'afb-offcanvas') . '</p>';
//     }
// }
// add_action('woocommerce_admin_order_data_after_shipping_address', 'afb_display_order_pay_info_in_admin');

/**
 * Modify order total calculation for order-pay page
 */
function afb_modify_order_total_for_pay($total, $order) {
    // Only apply on order-pay pages
    if (!is_wc_endpoint_url('order-pay')) {
        return $total;
    }
    
    $delivery_option = get_post_meta($order->get_id(), 'afb_delivery_option', true);
    $is_multiship = get_post_meta($order->get_id(), 'afb_is_multiship', true);
    $multiship_data = get_post_meta($order->get_id(), 'afb_multiship_data', true);
    
	 
    if ($delivery_option === 'pickup') {
        // For pickup, calculate total without any shipping costs or taxes
        $subtotal = $order->get_subtotal();
        
        // Only add product taxes, completely exclude shipping-related taxes
        $product_tax = 0;
        if (wc_tax_enabled()) {
            // Calculate product tax by getting line item taxes only
            $line_items = $order->get_items('line_item');
            foreach ($line_items as $item) {
                $item_taxes = $item->get_taxes();
                if (!empty($item_taxes['total'])) {
                    foreach ($item_taxes['total'] as $tax_amount) {
                        $product_tax += floatval($tax_amount);
                    }
                }
            }
        }
        
        $adjusted_total = $subtotal + $product_tax;
        return max(0, $adjusted_total);
        
    } elseif ($is_multiship && $multiship_data && is_array($multiship_data)) {
        // For multiship, apply custom shipping calculations
        $base_total = $order->get_subtotal() + $order->get_total_tax() - $order->get_shipping_tax();
        
        if (isset($multiship_data['multiplied_shipping'])) {
            $base_total += $multiship_data['multiplied_shipping'];
        }
        
        if (isset($multiship_data['multiplied_shipping_tax'])) {
            $base_total += $multiship_data['multiplied_shipping_tax'];
        }
        
        return $base_total;
    }
    
    return $total;
}
add_filter('woocommerce_order_get_total', 'afb_modify_order_total_for_pay', 10, 2);