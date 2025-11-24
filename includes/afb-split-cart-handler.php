<?php
/**
 * Admin-Ajax handler to split cart items into individual products with quantity 1
 * Add this to your theme's functions.php or plugin file
 */

// Prevent direct access
if (!defined('ABSPATH')) { exit; }

// Use native WP sanitization directly

// Hook for logged in users
add_action('wp_ajax_split_cart_items', 'handle_split_cart_items');
// Hook for non-logged in users (if needed)
add_action('wp_ajax_nopriv_split_cart_items', 'handle_split_cart_items');

function handle_split_cart_items() {
    // Verify nonce for security
    if (!wp_verify_nonce($_POST['nonce'], 'afb_checkout_nonce')) {
        wp_die(json_encode([
            'success' => false,
            'message' => 'Security check failed'
        ]));
    }
    
    // Check if WooCommerce is active
    if (!class_exists('WooCommerce')) {
        wp_die(json_encode([
            'success' => false,
            'message' => 'WooCommerce is not active'
        ]));
    }
    // Delegate to internal helper (bypasses direct POST reliance beyond delivery_option)
    $delivery_option = isset($_POST['delivery_option']) ? sanitize_text_field($_POST['delivery_option']) : '';
    $result = afb_split_cart_items_internal($delivery_option);
    wp_die(json_encode($result));
    
    try {
        // Get cart instance
        $cart = WC()->cart;
        
        // Get and save the delivery option from POST data
        $delivery_option = isset($_POST['delivery_option']) ? sanitize_text_field($_POST['delivery_option']) : '';
        if (!empty($delivery_option)) {
            WC()->session->set('afb_delivery_option', $delivery_option);
        }
        
        if ($cart->is_empty()) {
            wp_die(json_encode([
                'success' => false,
                'message' => 'Cart is empty'
            ]));
        }
        
        // Reset cart split state when combining items
        WC()->session->set('afb_cart_split', false);
        WC()->session->set('afb_multiship_data', null);
        
        // Reset cart split state when combining items
        WC()->session->set('afb_cart_split', false);
        WC()->session->set('afb_multiship_data', null);
        
        // Set cart split state to true in session
        WC()->session->set('afb_cart_split', true);
        
        $items_to_split = [];
        
        // Collect items that need splitting (quantity > 1)
        foreach ($cart->get_cart() as $cart_item_key => $cart_item) {
            if ($cart_item['quantity'] > 1) {
                $items_to_split[] = [
                    'key' => $cart_item_key,
                    'product_id' => $cart_item['product_id'],
                    'variation_id' => $cart_item['variation_id'],
                    'quantity' => $cart_item['quantity'],
                    'variation' => $cart_item['variation'],
                    'cart_item_data' => $cart_item // Use complete cart item data
                ];
            }
        }
        
        // if (empty($items_to_split)) {
        //     wp_die(json_encode([
        //         'success' => true,
        //         'message' => 'No items to split (all items already have quantity 1)',
        //         'items_processed' => 0
        //     ]));
        // }
        
        $total_items_added = 0;
        
        // Process each item that needs splitting
        foreach ($items_to_split as $item) {
            // Remove the original item
            $cart->remove_cart_item($item['key']);
            
            // Add individual items with quantity 1
            for ($i = 0; $i < $item['quantity']; $i++) {
                $added = $cart->add_to_cart(
                    $item['product_id'],
                    1, // quantity = 1
                    $item['variation_id'],
                    $item['variation'],
                    $item['cart_item_data'] // Use the complete original cart item data
                );
                
                if ($added) {
                    $total_items_added++;
                }
            }
        }
        
        // Calculate cart totals first to ensure shipping packages are available
        $cart->calculate_totals();
        
        // Apply shipping fee multiplication for multiship
        apply_multiship_shipping_fees($cart, $total_items_added);
        
        
        // Recalculate totals after applying multiship fees
        // $cart->calculate_totals();
        


         if (empty($items_to_split)) {
            wp_die(json_encode([
                'success' => true,
                'message' => 'No items to split (all items already have quantity 1)',
                'items_processed' => 0
            ]));
        }


        wp_die(json_encode([
            'success' => true,
            'message' => 'Cart items successfully split',
            'items_processed' => count($items_to_split),
            'total_items_added' => $total_items_added,
            'cart_count' => $cart->get_cart_contents_count()
        ]));
        
    } catch (Exception $e) {
        wp_die(json_encode([
            'success' => false,
            'message' => 'Error: ' . $e->getMessage()
        ]));
    }
}

/**
 * Internal helper to split cart items without AJAX/nonce/POST
 * Uses provided $delivery_option or falls back to session value.
 */
function afb_split_cart_items_internal($delivery_option = '') {
    try {
        // Ensure WooCommerce context
        if (!function_exists('WC') || !WC()) {
            return ['success' => false, 'message' => 'WooCommerce not initialized'];
        }

        // Resolve delivery option
if (empty($delivery_option)) {
    $delivery_option = WC()->session->get('afb_delivery_option', '');
}

// Save delivery option to session when provided
if (!empty($delivery_option)) {
    WC()->session->set('afb_delivery_option', sanitize_text_field($delivery_option));
}

        // Ensure cart exists
        if (!WC()->cart) { return ['success' => false, 'message' => 'Cart not initialized']; }
        $cart = WC()->cart;
        if ($cart->is_empty()) { return ['success' => true, 'message' => 'Cart is empty', 'items_processed' => 0]; }

        // Reset and set split state
        WC()->session->set('afb_cart_split', false);
        WC()->session->set('afb_multiship_data', null);
        WC()->session->set('afb_cart_split', true);

        // Collect items that need splitting
        $items_to_split = [];
        foreach ($cart->get_cart() as $cart_item_key => $cart_item) {
            if (!empty($cart_item['quantity']) && $cart_item['quantity'] > 1) {
                $items_to_split[] = [
                    'key' => $cart_item_key,
                    'product_id' => $cart_item['product_id'],
                    'variation_id' => $cart_item['variation_id'],
                    'quantity' => $cart_item['quantity'],
                    'variation' => $cart_item['variation'],
                    'cart_item_data' => $cart_item
                ];
            }
        }

        $total_items_added = 0;
        foreach ($items_to_split as $item) {
            $cart->remove_cart_item($item['key']);
            for ($i = 0; $i < $item['quantity']; $i++) {
                $added = $cart->add_to_cart(
                    $item['product_id'],
                    1,
                    $item['variation_id'],
                    $item['variation'],
                    $item['cart_item_data']
                );
                if ($added) { $total_items_added++; }
            }
        }

        // Calculate totals and apply multiship fees
        $cart->calculate_totals();
        apply_multiship_shipping_fees($cart, $total_items_added);

        if (empty($items_to_split)) {
            return ['success' => true, 'message' => 'No items to split (all quantities are 1)', 'items_processed' => 0];
        }

        return [
            'success' => true,
            'message' => 'Split completed',
            'items_processed' => count($items_to_split),
            'total_items_added' => $total_items_added,
            'cart_count' => $cart->get_cart_contents_count()
        ];

    } catch (Exception $e) {
        return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
    }
}

 


















 
/**
 * Admin-Ajax handler to combine same products in cart
 * Add this to your theme's functions.php or plugin file
 */

// Hook for logged in users
add_action('wp_ajax_combine_cart_items', 'handle_combine_cart_items');
// Hook for non-logged in users (if needed)
add_action('wp_ajax_nopriv_combine_cart_items', 'handle_combine_cart_items');

function handle_combine_cart_items() {
    // Verify nonce for security
    if (!wp_verify_nonce($_POST['nonce'], 'afb_checkout_nonce')) {
        wp_die(json_encode([
            'success' => false,
            'message' => 'Security check failed'
        ]));
    }
    
    // Check if WooCommerce is active
    if (!class_exists('WooCommerce')) {
        wp_die(json_encode([
            'success' => false,
            'message' => 'WooCommerce is not active'
        ]));
    }
    // Delegate to internal helper
    $delivery_option = isset($_POST['delivery_option']) ? sanitize_text_field($_POST['delivery_option']) : '';
    $result = afb_combine_cart_items_internal($delivery_option);
    wp_die(json_encode($result));
    
    try {
        // Get cart instance
        $cart = WC()->cart;
        
        // Get and save the delivery option from POST data
        // $delivery_option = isset($_POST['delivery_option']) ? sanitize_text_field($_POST['delivery_option']) : '';
        // if (!empty($delivery_option)) {
        //     WC()->session->set('afb_delivery_option', $delivery_option);
        // }
        
        // Get the delivery_option parameter (delivery option)
    $delivery_option = isset($_POST['delivery_option']) ? sanitize_text_field($_POST['delivery_option']) : '';
        if (!empty($delivery_option)) {
            WC()->session->set('afb_delivery_option', $delivery_option);
        }
        
        if ($cart->is_empty()) {
            wp_die(json_encode([
                'success' => false,
                'message' => 'Cart is empty'
            ]));
        }
        
        $items_to_combine = [];
        $original_cart_count = $cart->get_cart_contents_count();
        
        // Group identical products based on product_id and variation_id only
        foreach ($cart->get_cart() as $cart_item_key => $cart_item) {
            // Create a simple identifier for same products
            // Only check product_id and variation_id (if exists)
            $product_hash = $cart_item['product_id'];
            if ($cart_item['variation_id']) {
                $product_hash .= '_' . $cart_item['variation_id'];
            }
            
            if (!isset($items_to_combine[$product_hash])) {
                $items_to_combine[$product_hash] = [
                    'product_id' => $cart_item['product_id'],
                    'variation_id' => $cart_item['variation_id'],
                    'variation' => $cart_item['variation'],
                    'cart_item_data' => $cart_item,
                    'total_quantity' => 0,
                    'cart_keys' => [],
                    'first_item' => $cart_item
                ];
            }
            
            $items_to_combine[$product_hash]['total_quantity'] += $cart_item['quantity'];
            $items_to_combine[$product_hash]['cart_keys'][] = $cart_item_key;
        }
        
        $items_combined = 0;
        $items_removed = 0;
        
        // Process items that have duplicates
        foreach ($items_to_combine as $hash => $item_data) {
            // Only process if there are multiple instances of the same product
            if (count($item_data['cart_keys']) > 1) {
                // Remove all existing instances
                foreach ($item_data['cart_keys'] as $cart_key) {
                    $cart->remove_cart_item($cart_key);
                    $items_removed++;
                }
                
                // Add single combined item with first item's data
                $added = $cart->add_to_cart(
                    $item_data['product_id'],
                    $item_data['total_quantity'],
                    $item_data['variation_id'],
                    $item_data['variation'],
                    $item_data['first_item'] // Use the complete first item data
                );
                
                if ($added) {
                    $items_combined++;
                }
            }
        }
        
        // Reset shipping fees to single application for combine
        if ($delivery_option === 'pickup') {
            // For pickup orders, set shipping to free
            reset_shipping_to_free($cart);
        } else {
            // For other delivery options, reset to single shipping fees
            reset_single_shipping_fees($cart);
        }
        
        // Calculate cart totals
        $cart->calculate_totals();
        
        $new_cart_count = $cart->get_cart_contents_count();
        
        if ($items_combined > 0) {
            wp_die(json_encode([
                'success' => true,
                'message' => 'Cart items successfully combined',
                'items_combined' => $items_combined,
                'items_removed' => $items_removed,
                'original_cart_lines' => count($cart->get_cart()),
                'new_cart_lines' => count($cart->get_cart()),
                'total_quantity_before' => $original_cart_count,
                'total_quantity_after' => $new_cart_count
            ]));
        } else {
            wp_die(json_encode([
                'success' => true,
                'message' => 'No duplicate items found to combine',
                'items_combined' => 0
            ]));
        }
        
    } catch (Exception $e) {
        wp_die(json_encode([
            'success' => false,
            'message' => 'Error: ' . $e->getMessage()
        ]));
    }
}

/**
 * Internal helper to combine identical cart items without AJAX/nonce/POST
 * Uses provided $delivery_option or session value and enforces shipping state.
 */
function afb_combine_cart_items_internal($delivery_option = '') {
    try {
        // Ensure WooCommerce context
        if (!function_exists('WC') || !WC()) {
            return ['success' => false, 'message' => 'WooCommerce not initialized'];
        }

        // Resolve delivery option
        if (empty($delivery_option)) {
            $delivery_option = WC()->session->get('afb_delivery_option', '');
        }
        if (!empty($delivery_option)) {
            WC()->session->set('afb_delivery_option', sanitize_text_field($delivery_option));
        }

        // Ensure cart exists
        if (!WC()->cart) { return ['success' => false, 'message' => 'Cart not initialized']; }
        $cart = WC()->cart;
        if ($cart->is_empty()) { return ['success' => true, 'message' => 'Cart is empty']; }

        $items_to_combine = [];
        $original_cart_count = $cart->get_cart_contents_count();

        foreach ($cart->get_cart() as $cart_item_key => $cart_item) {
            $product_hash = $cart_item['product_id'];
            if (!empty($cart_item['variation_id'])) {
                $product_hash .= '_' . $cart_item['variation_id'];
            }

            if (!isset($items_to_combine[$product_hash])) {
                $items_to_combine[$product_hash] = [
                    'product_id' => $cart_item['product_id'],
                    'variation_id' => $cart_item['variation_id'],
                    'variation' => $cart_item['variation'],
                    'cart_item_data' => $cart_item,
                    'total_quantity' => 0,
                    'cart_keys' => [],
                    'first_item' => $cart_item
                ];
            }

            $items_to_combine[$product_hash]['total_quantity'] += $cart_item['quantity'];
            $items_to_combine[$product_hash]['cart_keys'][] = $cart_item_key;
        }

        $items_combined = 0;
        $items_removed = 0;

        foreach ($items_to_combine as $hash => $item_data) {
            if (count($item_data['cart_keys']) > 1) {
                foreach ($item_data['cart_keys'] as $cart_key) {
                    $cart->remove_cart_item($cart_key);
                    $items_removed++;
                }

                $added = $cart->add_to_cart(
                    $item_data['product_id'],
                    $item_data['total_quantity'],
                    $item_data['variation_id'],
                    $item_data['variation'],
                    $item_data['first_item']
                );
                if ($added) { $items_combined++; }
            }
        }

        // Enforce shipping/pricing according to delivery option
        if ($delivery_option === 'pickup') {
            reset_shipping_to_free($cart);
        } else {
            reset_single_shipping_fees($cart);
        }

        $cart->calculate_totals();

        // Mark as not split
        WC()->session->set('afb_cart_split', false);

        $new_cart_count = $cart->get_cart_contents_count();

        if ($items_combined > 0) {
            return [
                'success' => true,
                'message' => 'Combine completed',
                'items_combined' => $items_combined,
                'items_removed' => $items_removed,
                'original_cart_lines' => count($cart->get_cart()),
                'new_cart_lines' => count($cart->get_cart()),
                'total_quantity_before' => $original_cart_count,
                'total_quantity_after' => $new_cart_count
            ];
        }

        return ['success' => true, 'message' => 'No duplicates to combine', 'items_combined' => 0];

    } catch (Exception $e) {
        return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
    }
}

/**
 * Helper function to get product display name for debugging
 */
function get_cart_item_name($cart_item) {
    $product = $cart_item['data'];
    $product_name = $product->get_name();
    
    if ($cart_item['variation_id']) {
        $variation = new WC_Product_Variation($cart_item['variation_id']);
        $attrs = $variation->get_variation_attributes();
        $product_name .= ' - ' . implode(', ', (array) $attrs);
    }
    
    return $product_name;
}

/**
 * Apply shipping fee multiplication for multiship functionality
 * When items are split, multiply shipping fees and taxes by the number of individual items
 */
function apply_multiship_shipping_fees($cart, $total_items_added) {
    
    // Get cart item count for multiplication
    // Use number of separate cart lines, not total quantity
    $cart_item_count = is_array($cart->get_cart()) ? count($cart->get_cart()) : 0;
    error_log('Cart item count: ' . $cart_item_count);
    
    // If only 1 item, no need to multiply
    // if ($cart_item_count <= 1) {
    //     error_log('Only 1 or no items in cart, skipping multiplication');
    //     return;
    // }
    
    // IMPORTANT: Get shipping totals BEFORE removing existing fees
    $shipping_total = WC()->cart->get_shipping_total();
    $shipping_tax_total = WC()->cart->get_shipping_tax();
    
    // Get cart taxes total BEFORE removing existing fees
    $cart_taxes_total = WC()->cart->get_taxes_total();
    
   
    if ($shipping_total > 0) {
        // Calculate multiplied amounts
        $multiplied_shipping_cost = $shipping_total * $cart_item_count;
        $multiplied_shipping_taxes = $shipping_tax_total * $cart_item_count;
         
        // NOW remove existing shipping fees after we got their values
        remove_existing_shipping_fees($cart);
        
        // Add the multiplied shipping fee (non-taxable since we're handling taxes manually)
        WC()->cart->add_fee(__('Shipping (Multiship)', 'afb-offcanvas'), $multiplied_shipping_cost, false);
        
        // Add the multiplied tax as a separate fee
        if ($multiplied_shipping_taxes > 0) {
            WC()->cart->add_fee(__('Shipping Tax (Multiship)', 'afb-offcanvas'), $multiplied_shipping_taxes, false);
        }
        
        // Store multiship calculation data in session for display purposes
        WC()->session->set('afb_multiship_data', [
            'original_shipping' => $shipping_total,
            'original_shipping_tax' => $shipping_tax_total,
            'cart_item_count' => $cart_item_count,
            'multiplied_shipping' => $multiplied_shipping_cost,
            'multiplied_shipping_tax' => $multiplied_shipping_taxes,
            // Include delivery type for downstream usage (e.g., order meta/admin display)
            'delivery_type' => WC()->session->get('afb_delivery_option', '')
        ]);
        
        error_log('Fees added successfully');
    } else {
        error_log('No shipping total found: ' . $shipping_total);
    }
    
    // Handle cart taxes multiplication
    if ($cart_taxes_total > 0) {
        // Calculate multiplied cart taxes (excluding shipping taxes)
        $multiplied_cart_taxes = $cart_taxes_total * $cart_item_count;
        
        error_log('Multiplied cart taxes: ' . $multiplied_cart_taxes);
        
        // Remove existing cart tax fees first
        remove_existing_cart_tax_fees($cart);
        
        // Add the multiplied cart tax as a separate fee
        $cart->add_fee(__('Taxes (Multiship)', 'afb-offcanvas'), $multiplied_cart_taxes, false);
        
        error_log('Cart tax fees added successfully');
    } else {
        error_log('No cart taxes found: ' . $cart_taxes_total);
    }
}

/**
 * Remove existing shipping fees from cart
 * Removes both shipping fees and shipping tax fees for multiship functionality
 */
function remove_existing_shipping_fees($cart) {
    // Get all fees
    $fees = $cart->get_fees();
    
    // Remove shipping-related fees (including shipping taxes)
    foreach ($fees as $fee_key => $fee) {
        if (strpos(strtolower($fee->name), 'shipping') !== false || 
            strpos(strtolower($fee->name), 'livraison') !== false) {
            unset($cart->fees_api()->fees[$fee_key]);
        }
    }
}

/**
 * Remove existing cart tax fees (multiship taxes)
 */
function remove_existing_cart_tax_fees($cart) {
    // Get all fees
    $fees = $cart->get_fees();
    
    // Remove tax-related fees (multiship taxes)
    foreach ($fees as $fee_key => $fee) {
        if (strpos(strtolower($fee->name), 'taxes (multiship)') !== false) {
            unset($cart->fees_api()->fees[$fee_key]);
        }
    }
    
    // Clear multiship session data when removing tax fees
    WC()->session->__unset('afb_multiship_data');
}

/**
 * Reset shipping fees to single application for combine functionality
 * Removes multiplied shipping fees and taxes, allowing standard WooCommerce calculation
 */
function reset_single_shipping_fees($cart) {
    // Remove existing multiship shipping fees and taxes
    remove_existing_shipping_fees($cart);
    
    // Remove existing multiship cart tax fees
    remove_existing_cart_tax_fees($cart);
    
    // Clear multiship session data when resetting to single shipping
    WC()->session->__unset('afb_multiship_data');
    
    // Let WooCommerce calculate shipping and taxes normally
    // The standard shipping calculation will apply single fees and taxes
}

/**
 * Reset shipping to free for pickup orders
 * Removes all shipping fees and sets shipping to free
 */
function reset_shipping_to_free($cart) {
    // Remove existing multiship shipping fees and taxes
    remove_existing_shipping_fees($cart);
    
    // Add a free shipping fee to override any shipping costs
    $cart->add_fee(__('Livraison', 'afb-offcanvas'), 0);
}