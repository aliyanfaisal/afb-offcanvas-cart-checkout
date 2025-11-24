<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Sanitizers that rely directly on WP/WC environment
function afb_sanitize_number($value) {
    return (float) wc_format_decimal($value);
}

// Save posted order summary fields into order meta during checkout
add_action('woocommerce_checkout_create_order', function ($order, $data) {
    if (!isset($_POST['afb_summary']) || !is_array($_POST['afb_summary'])) {
        return;
    }

    $summary = $_POST['afb_summary'];

    // Whitelisted numeric keys
    $numeric_keys = [
        'subtotal',
        'fees_total',
        'shipping_total',
        'shipping_tax_total',
        'tax_total',
        'shipping_fee_total',
        'base_total',
        'total',
        'cart_item_count',
        'original_shipping',
        'original_shipping_tax',
        'multiplied_shipping',
        'multiplied_shipping_tax',
    ];

    foreach ($numeric_keys as $key) {
        if (isset($summary[$key])) {
            $order->update_meta_data('summary_' . $key, afb_sanitize_number($summary[$key]));
        }
    }

    // Text keys
    $text_keys = [ 'delivery_option' ];
    foreach ($text_keys as $key) {
        if (isset($summary[$key])) {
            $order->update_meta_data('summary_' . $key, sanitize_text_field($summary[$key]));
        }
    }

    // Boolean flags
    if (isset($summary['is_cart_split'])) {
        $order->update_meta_data('summary_is_cart_split', (int) !!$summary['is_cart_split']);
    }

    // Mirror expected meta keys for other handlers
    $delivery_option = isset($summary['delivery_option']) ? sanitize_text_field($summary['delivery_option']) : '';
    if ($delivery_option !== '') {
        $order->update_meta_data('afb_delivery_option', $delivery_option);
    }
    $is_split = isset($summary['is_cart_split']) ? ((int) !!$summary['is_cart_split']) : 0;
    $order->update_meta_data('afb_is_multiship', $is_split ? 1 : 0);

    // Apply shipping totals directly to the order
    $target_shipping_total = 0.0;
    $target_shipping_tax = 0.0;
    if ($delivery_option === 'pickup') {
        $target_shipping_total = 0.0;
        $target_shipping_tax = 0.0;
    } elseif ($is_split && isset($summary['multiplied_shipping'])) {
        $target_shipping_total = afb_sanitize_number($summary['multiplied_shipping']);
        $target_shipping_tax = afb_sanitize_number(isset($summary['multiplied_shipping_tax']) ? $summary['multiplied_shipping_tax'] : 0);
    } else {
        $target_shipping_total = afb_sanitize_number(isset($summary['shipping_total']) ? $summary['shipping_total'] : 0);
        $target_shipping_tax = afb_sanitize_number(isset($summary['shipping_tax_total']) ? $summary['shipping_tax_total'] : 0);
    }
    $order->set_shipping_total($target_shipping_total);
    $order->set_shipping_tax($target_shipping_tax);
    $order->calculate_totals(false);
}, 10, 2);

// Ensure the created shipping item reflects multiplied shipping totals
add_action('woocommerce_checkout_create_order_shipping_item', function ($item, $package_key, $package, $order) {
    if (!isset($_POST['afb_summary']) || !is_array($_POST['afb_summary'])) {
        return;
    }
    $summary = $_POST['afb_summary'];
    $delivery_option = isset($summary['delivery_option']) ? sanitize_text_field($summary['delivery_option']) : '';
    $is_split = isset($summary['is_cart_split']) ? ((int) !!$summary['is_cart_split']) : 0;

    if ($delivery_option === 'pickup') {
        $item->set_total(0.0);
        $item->set_taxes(['total' => [], 'subtotal' => []]);
        $order->set_shipping_tax(0.0);
        return;
    }

    $target_shipping_total = 0.0;
    $target_shipping_tax = 0.0;
    $original_shipping_tax = isset($summary['original_shipping_tax']) ? afb_sanitize_number($summary['original_shipping_tax']) : 0.0;
    if ($is_split && isset($summary['multiplied_shipping'])) {
        $target_shipping_total = afb_sanitize_number($summary['multiplied_shipping']);
        $target_shipping_tax = afb_sanitize_number(isset($summary['multiplied_shipping_tax']) ? $summary['multiplied_shipping_tax'] : 0);
    } else {
        $target_shipping_total = afb_sanitize_number(isset($summary['shipping_total']) ? $summary['shipping_total'] : 0);
        $target_shipping_tax = afb_sanitize_number(isset($summary['shipping_tax_total']) ? $summary['shipping_tax_total'] : 0);
    }

    $item->set_total($target_shipping_total);
    // Scale existing tax distribution on the shipping item to match target tax
    $taxes = $item->get_taxes();
    $ratio = ($original_shipping_tax > 0.0 && $target_shipping_tax > 0.0) ? ($target_shipping_tax / $original_shipping_tax) : 0.0;
    if ($ratio > 0.0 && is_array($taxes) && !empty($taxes['total'])) {
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
    } elseif ($target_shipping_tax <= 0.0) {
        // Ensure no taxes stored on the shipping item when target tax is zero
        $item->set_taxes(['total' => [], 'subtotal' => []]);
    }

    // Ensure order-level shipping tax matches target
    $order->set_shipping_tax($target_shipping_tax);
}, 10, 4);

// After order items are created, remove multiship fee items so shipping appears only under Shipping
add_action('woocommerce_checkout_update_order_meta', function ($order_id) {
    $order = wc_get_order($order_id);
    if (!$order) { return; }

    // Convert single shipping to per-item shipping when in multiship (split) mode
    $delivery_option        = (string) $order->get_meta('summary_delivery_option');
    $is_split               = (int) $order->get_meta('summary_is_cart_split');
    $cart_item_count        = (int) $order->get_meta('summary_cart_item_count');
    $original_shipping      = (float) $order->get_meta('summary_original_shipping');
    $original_ship_tax      = (float) $order->get_meta('summary_original_shipping_tax');
    $multiplied_shipping    = (float) $order->get_meta('summary_multiplied_shipping');
    $multiplied_shipping_tax= (float) $order->get_meta('summary_multiplied_shipping_tax');

    if ($delivery_option !== 'pickup' && $is_split === 1 && $cart_item_count > 1 && $original_shipping > 0) {
        // Capture existing shipping item data (method + taxes) to replicate per line item
        $existing_ship_items = $order->get_items('shipping');
        $base_method_title = '';
        $base_method_id = '';
        $base_taxes = ['total' => [], 'subtotal' => []];
        $base_taxes_sum_total = 0.0;
        if (!empty($existing_ship_items)) {
            $first = reset($existing_ship_items);
            if ($first) {
                $base_method_title = method_exists($first, 'get_method_title') ? (string) $first->get_method_title() : '';
                $base_method_id    = method_exists($first, 'get_method_id') ? (string) $first->get_method_id() : '';
                $taxes             = method_exists($first, 'get_taxes') ? $first->get_taxes() : [];
                if (is_array($taxes)) { 
                    $base_taxes = $taxes; 
                    if (!empty($taxes['total']) && is_array($taxes['total'])) {
                        foreach ($taxes['total'] as $amt) { $base_taxes_sum_total += (float) $amt; }
                    }
                }
            }
            // Remove all existing shipping items before re-adding per-item ones
            foreach ($existing_ship_items as $sid => $si) { $order->remove_item($sid); }
        }

        // Determine per-item shipping and per-item tax amounts
        $per_item_shipping   = 0.0;
        $per_item_ship_tax   = 0.0;
        if ($cart_item_count > 0) {
            if ($multiplied_shipping > 0) {
                $per_item_shipping = (float) wc_format_decimal($multiplied_shipping / $cart_item_count);
            } else {
                $per_item_shipping = (float) wc_format_decimal($original_shipping);
            }
            if ($multiplied_shipping_tax > 0) {
                $per_item_ship_tax = (float) wc_format_decimal($multiplied_shipping_tax / $cart_item_count);
            } else {
                $per_item_ship_tax = (float) wc_format_decimal($original_ship_tax);
            }
        }

        // Create one shipping item per product line item
        $line_items = $order->get_items('line_item');
        $added = 0;
        foreach ($line_items as $line_item_id => $line_item) {
            $ship = new WC_Order_Item_Shipping();
            if ($base_method_title !== '') { $ship->set_method_title($base_method_title); }
            if ($base_method_id !== '') { $ship->set_method_id($base_method_id); }
            $ship->set_total($per_item_shipping);
            if ($per_item_ship_tax > 0 && !empty($base_taxes) && !empty($base_taxes['total']) && $base_taxes_sum_total > 0) {
                // Scale tax map from the existing shipping item down to per-item values
                $scaled_total = [];
                foreach ($base_taxes['total'] as $rate_id => $amt) {
                    $ratio = $per_item_ship_tax / $base_taxes_sum_total;
                    $scaled_total[$rate_id] = (float) wc_format_decimal(((float)$amt) * $ratio);
                }
                $scaled_subtotal = [];
                if (!empty($base_taxes['subtotal'])) {
                    foreach ($base_taxes['subtotal'] as $rate_id => $amt) {
                        $ratio = $per_item_ship_tax / $base_taxes_sum_total;
                        $scaled_subtotal[$rate_id] = (float) wc_format_decimal(((float)$amt) * $ratio);
                    }
                }
                $ship->set_taxes(['total' => $scaled_total, 'subtotal' => $scaled_subtotal]);
            } else {
                $ship->set_taxes(['total' => [], 'subtotal' => []]);
            }
            // Link shipping item to its corresponding line item for clarity
            $ship->update_meta_data('afb_shipping_for_line_item', (int) $line_item_id);
            $order->add_item($ship);
            $added++;
        }

        if ($added > 0) {
            $order->set_shipping_total((float) wc_format_decimal($per_item_shipping * $added));
            $order->set_shipping_tax((float) wc_format_decimal($per_item_ship_tax * $added));
        }
    }

    // Read summary meta to decide whether tax fee should be removed
    $is_split = (int) $order->get_meta('summary_is_cart_split');
    $delivery_option = (string) $order->get_meta('summary_delivery_option');
    $target_ship_tax = (float) $order->get_meta('summary_multiplied_shipping_tax');

    $shipping_tax_sum = 0.0;
    foreach ($order->get_items('shipping') as $ship_item) {
        $taxes = $ship_item->get_taxes();
        if (is_array($taxes) && !empty($taxes['total'])) {
            foreach ($taxes['total'] as $amt) { $shipping_tax_sum += (float) $amt; }
        }
    }

    $fee_items = $order->get_items('fee');
    foreach ($fee_items as $item_id => $item) {
        $name = $item->get_name();
        $key = strtolower(trim($name));
        if ($key === 'shipping (multiship)') {
            // Always remove the duplicated shipping fee once shipping totals are set on the order
            $order->remove_item($item_id);
            continue;
        }
        if ($key === 'shipping tax (multiship)') {
            // Remove tax fee only if shipping item taxes hold the multiplied tax
            $should_remove_tax_fee = ($delivery_option === 'pickup') || ($is_split && $target_ship_tax > 0 && abs($shipping_tax_sum - $target_ship_tax) < 0.01);
            if ($should_remove_tax_fee) {
                $order->remove_item($item_id);
            }
        }
    }
    $order->calculate_totals(false);
    $order->save();
}, 10, 1);