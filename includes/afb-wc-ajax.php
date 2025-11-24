<?php
/**
 * AFB WooCommerce AJAX Handlers
 * Dynamic order review functionality
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}


/**
 * Render order review content
 * This function generates the dynamic order review HTML
 */
function afb_render_order_review_content() {
    // Before rendering, enforce cart state based on delivery option
    try {
        if (function_exists('WC') && class_exists('WooCommerce') && WC()->cart) {
            $delivery_option = WC()->session->get('afb_delivery_option', '');
            $is_split = WC()->session->get('afb_cart_split', false);
            $cart = WC()->cart;

            // Decide whether to split or combine
            if ($delivery_option === 'multiship') {
                // Ensure split state and fees reflect multiship
                if (!$is_split) {
                    if (function_exists('afb_split_cart_items_internal')) {
                        afb_split_cart_items_internal($delivery_option);
                    }
                } else {
                    // Already split: ensure fees are applied in case totals were reset
                    $cart->calculate_totals();
                    // Use cart items count to reapply fees conservatively
                    // $count = $cart->get_cart_contents_count();
                    $count = is_array($cart->get_cart()) ? count($cart->get_cart()) : 0;
                    if (function_exists('apply_multiship_shipping_fees')) {
                        apply_multiship_shipping_fees($cart, $count);
                    }
                }
            } else {
                // Non-multiship: ensure combined state and normal fees
                if ($is_split || WC()->session->get('afb_multiship_data')) {
                    if (function_exists('afb_combine_cart_items_internal')) {
                        afb_combine_cart_items_internal($delivery_option);
                    } else {
                        // Fallback: reset fees
                        if ($delivery_option === 'pickup') {
                            if (function_exists('reset_shipping_to_free')) { reset_shipping_to_free($cart); }
                        } else {
                            if (function_exists('reset_single_shipping_fees')) { reset_single_shipping_fees($cart); }
                        }
                        WC()->session->set('afb_cart_split', false);
                        $cart->calculate_totals();
                    }
                }
            }
        }
    } catch (Exception $e) {
        // Silently ignore to avoid breaking render; consider logging in debug
    }

    ob_start();
    ?>
    
    <div class="afb-order-header">
        <div class="afb-order-col-product"><?php esc_html_e("Article", "afb-offcanvas"); ?></div>
        <div class="afb-order-col-qty"><?php esc_html_e("Quantité", "afb-offcanvas"); ?></div>
        <div class="afb-order-col-price"><?php esc_html_e("Prix", "afb-offcanvas"); ?></div>
        <div class="afb-order-col-total"><?php esc_html_e("Total", "afb-offcanvas"); ?></div>
    </div>

    <ul class="afb-order-items">
        <?php if (WC()->cart && !WC()->cart->is_empty()): ?>
            <?php foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item): ?>
                <?php
                $_product = apply_filters('woocommerce_cart_item_product', $cart_item['data'], $cart_item, $cart_item_key);
                $product_id = apply_filters('woocommerce_cart_item_product_id', $cart_item['product_id'], $cart_item, $cart_item_key);
                $thumbnail = apply_filters('woocommerce_cart_item_thumbnail', $_product->get_image(), $cart_item, $cart_item_key);
                $product_permalink = apply_filters('woocommerce_cart_item_permalink', $_product->is_visible() ? $_product->get_permalink($cart_item) : '', $cart_item, $cart_item_key);
                ?>
                <li class="afb-order-item" data-cart-key="<?php echo esc_attr($cart_item_key); ?>" data-product-id="<?php echo esc_attr($product_id); ?>">
                    <div class="afb-order-item-content">
                        <div class="afb-order-item-image">
                            <?php if (!$product_permalink): ?>
                                <?php echo $thumbnail; ?>
                            <?php else: ?>
                                <a href="<?php echo esc_url($product_permalink); ?>">
                                    <?php echo $thumbnail; ?>
                                </a>
							
							<span class="mobile afb-mobile-title">
								<?php if (!$product_permalink): ?>
									<?php echo esc_html($_product->get_name()); ?>
								<?php else: ?>
									<a href="<?php echo esc_url($product_permalink); ?>">
										<?php echo esc_html($_product->get_name()); ?>
									</a>
								<?php endif; ?>
							</span>
								
                            <?php endif; ?>
                        </div>
                        
                        <div class="afb-order-item-name">
                            <?php if (!$product_permalink): ?>
                                <?php echo esc_html($_product->get_name()); ?>
                            <?php else: ?>
                                <a href="<?php echo esc_url($product_permalink); ?>">
                                    <?php echo esc_html($_product->get_name()); ?>
                                </a>
                            <?php endif; ?>
                            
                            <?php
                            // Display item data/variations
                            $item_data = WC()->cart->get_item_data($cart_item);
                            if ($item_data) {
                                echo '<div class="afb-order-item-meta">' . $item_data . '</div>';
                            }
                            ?>
                        </div>

                        <div class="afb-order-item-qty">
                            <div class="afb-quantity">
                                <button type="button" class="afb-quantity-minus" data-cart-key="<?php echo esc_attr($cart_item_key); ?>">−</button>
                                <input type="number"
                                    value="<?php echo esc_attr($cart_item['quantity']); ?>" 
                                    min="0"
                                    max="999"
                                    class="afb-quantity-input"
                                    data-cart-key="<?php echo esc_attr($cart_item_key); ?>">
                                <button type="button" class="afb-quantity-plus" data-cart-key="<?php echo esc_attr($cart_item_key); ?>">+</button>
                            </div>
                        </div>

                        <div class="afb-order-item-price">
                            <?php echo WC()->cart->get_product_price($_product); ?>
                        </div>

                        <div class="afb-order-item-total">
                            <?php echo WC()->cart->get_product_subtotal($_product, $cart_item['quantity']); ?>
                        </div>

                        <div class="afb-order-item-remove">
                            <a href="#" class="afb-cart-remove" data-cart-key="<?php echo esc_attr($cart_item_key); ?>">
                                <svg width="24px" height="24px" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                    <g data-name="Layer 2">
                                        <g data-name="close">
                                            <rect width="24" height="24" transform="rotate(180 12 12)" opacity="0"></rect>
                                            <path d="M13.41 12l4.3-4.29a1 1 0 1 0-1.42-1.42L12 10.59l-4.29-4.3a1 1 0 0 0-1.42 1.42l4.3 4.29-4.3 4.29a1 1 0 0 0 0 1.42 1 1 0 0 0 1.42 0l4.29-4.3 4.29 4.3a1 1 0 0 0 1.42 0 1 1 0 0 0 0-1.42z"></path>
                                        </g>
                                    </g>
                                </svg>
                            </a>
                        </div>
                    </div>
                </li>
            <?php endforeach; ?>
        <?php else: ?>
            <li class="afb-order-empty">
                <p><?php esc_html_e("Votre panier est vide.", "afb-offcanvas"); ?></p>
            </li>
        <?php endif; ?>
    </ul>

    <div class="afb-order-summary">
        <?php if (WC()->cart && !WC()->cart->is_empty()): ?>
            <div class="afb-order-summary-row">
                <div class="afb-order-summary-label"><?php esc_html_e("Sous-total", "afb-offcanvas"); ?></div>
                <div class="afb-order-summary-value"><?php echo WC()->cart->get_cart_subtotal(); ?></div>
            </div>
            
            <?php if (WC()->cart->needs_shipping() && WC()->cart->show_shipping()): ?>
                <div class="afb-order-summary-row">
                    <div class="afb-order-summary-label"><?php esc_html_e("Livraison", "afb-offcanvas"); ?></div>
                    <div class="afb-order-summary-value">
                        <?php 
                        // Check if cart is in split mode
                        $is_cart_split = WC()->session->get('afb_cart_split', false);
                        $multiship_data = WC()->session->get('afb_multiship_data', null);
                        
                        // Check if delivery option is pickup (free shipping)
                        $delivery_option = WC()->session->get('afb_delivery_option', '');
                        
                        if ($delivery_option === 'pickup') {
                            // Display free shipping for pickup
                            echo esc_html__("Gratuit", "afb-offcanvas");
                        } elseif ($is_cart_split && $multiship_data) {
                            // Display multiship shipping as: quantity × original fee = total
                            $qty = isset($multiship_data['cart_item_count']) ? (int) $multiship_data['cart_item_count'] : 0;
                            $orig_ship = isset($multiship_data['original_shipping']) ? $multiship_data['original_shipping'] : 0;
                            $multi_ship = isset($multiship_data['multiplied_shipping']) ? $multiship_data['multiplied_shipping'] : 0;
                            echo sprintf('%d × %s = %s', $qty, wc_price($orig_ship), wc_price($multi_ship));
                        } else {
                            // Default shipping display logic
                            $shipping_total = WC()->cart->get_cart_shipping_total();
                            // Display custom fees (like multiship shipping fees)
                            $fees = WC()->cart->get_fees();
                            $shipping_fee_total = 0;
                            foreach ($fees as $fee) {
                                if (strpos(strtolower($fee->name), 'shipping') !== false || 
                                    strpos(strtolower($fee->name), 'livraison') !== false) {
                                    $shipping_fee_total += $fee->amount;
                                }
                            }
                            
                            if ($shipping_fee_total > 0) {
                                echo wc_price($shipping_fee_total);
                            } else {
                                echo $shipping_total ? $shipping_total : esc_html__("À calculer", "afb-offcanvas");
                            }
                        }
                        ?>
                    </div>
 
 
                </div>
            <?php endif; ?>
            
            <?php if (wc_tax_enabled() && !WC()->cart->display_prices_including_tax() && WC()->cart->get_taxes_total()): ?>
                <div class="afb-order-summary-row">
                    <div class="afb-order-summary-label"><?php esc_html_e("Taxes", "afb-offcanvas"); ?></div>
                    <div class="afb-order-summary-value">
                        <?php 
                        // Check if there are multiship tax fees
                        $is_cart_split = WC()->session->get('afb_cart_split', false);
                        $multiship_data = WC()->session->get('afb_multiship_data', null);
                        
                        if ($is_cart_split && $multiship_data && $multiship_data['multiplied_shipping_tax'] > 0) {
                            // Display multiship shipping tax as: quantity × original tax = total tax
                            $qty = isset($multiship_data['cart_item_count']) ? (int) $multiship_data['cart_item_count'] : 0;
                            $orig_ship_tax = isset($multiship_data['original_shipping_tax']) ? $multiship_data['original_shipping_tax'] : 0;
                            $multi_ship_tax = isset($multiship_data['multiplied_shipping_tax']) ? $multiship_data['multiplied_shipping_tax'] : 0;
                            echo sprintf('%d × %s = %s', $qty, wc_price($orig_ship_tax), wc_price($multi_ship_tax));
                        } else {
                            // Default tax display logic
                            $multiship_tax_total = 0;
                            $fees = WC()->cart->get_fees();
                            
                            foreach ($fees as $fee) {
                                if (strpos(strtolower($fee->name), 'shipping tax (multiship)') !== false) {
                                    $multiship_tax_total += $fee->amount;
                                }
                            }
                            
                            if ($multiship_tax_total > 0) {
                                // Display multiship taxes
                                echo wc_price($multiship_tax_total);
                            } else {
                                // Display regular taxes
                                echo WC()->cart->get_taxes_total();
                            }
                        }
                        ?>
                    </div>
                </div>
            <?php endif; ?>
            
            <div class="afb-order-summary-row afb-order-total">
                <div class="afb-order-summary-label"><?php esc_html_e("Total", "afb-offcanvas"); ?></div>
                <div class="afb-order-summary-value">
                    <?php 
                    // Check delivery option first
                    $delivery_option = WC()->session->get('afb_delivery_option', '');
                    $is_cart_split = WC()->session->get('afb_cart_split', false);
                    $multiship_data = WC()->session->get('afb_multiship_data', null);
                    
                    if ($delivery_option === 'pickup') {
                        // For pickup orders, subtract shipping costs from total
                        $base_total = WC()->cart->get_total('edit'); // Get numeric value
                        $shipping_total = WC()->cart->get_shipping_total();
                        $shipping_tax_total = WC()->cart->get_shipping_tax();
                        
                        // Calculate total without shipping costs
                        $pickup_total = $base_total - $shipping_total - $shipping_tax_total;
                        
                        echo wc_price($pickup_total);
                    } elseif ($is_cart_split && $multiship_data) {
                        // Calculate total with multiship adjustments
                        $base_total = WC()->cart->get_total('edit'); // Get numeric value
                        $original_shipping = $multiship_data['original_shipping'];
                        $original_shipping_tax = $multiship_data['original_shipping_tax'];
                        $multiplied_shipping = $multiship_data['multiplied_shipping'];
                        $multiplied_shipping_tax = $multiship_data['multiplied_shipping_tax'];
                        
                        // Adjust total: remove original shipping/tax, add multiplied amounts
                        $adjusted_total = $base_total - $original_shipping - $original_shipping_tax + $multiplied_shipping + $multiplied_shipping_tax;
                        
                        echo wc_price($adjusted_total);
                    } else {
                        // Regular total calculation
                        echo WC()->cart->get_total();
                    }
                    ?>
                </div>
            </div>
            <?php
            // Collect numeric values for hidden inputs to save into order meta
            $delivery_option      = WC()->session->get('afb_delivery_option', '');
            $is_cart_split        = WC()->session->get('afb_cart_split', false);
            $multiship_data       = WC()->session->get('afb_multiship_data', null);
            $subtotal_num         = WC()->cart->get_subtotal();
            $shipping_total_num   = WC()->cart->get_shipping_total();
            $shipping_tax_total   = WC()->cart->get_shipping_tax();
            $tax_total_num        = WC()->cart->get_taxes_total();
            $base_total_num       = WC()->cart->get_total('edit');
            $fees                 = WC()->cart->get_fees();
            $fees_total_num       = 0.0;
            $shipping_fee_total   = 0.0;
            foreach ($fees as $fee) {
                $amount = is_object($fee) && isset($fee->amount) ? (float)$fee->amount : 0.0;
                $fees_total_num += $amount;
                $lname = is_object($fee) && isset($fee->name) ? strtolower($fee->name) : '';
                if (strpos($lname, 'shipping') !== false || strpos($lname, 'livraison') !== false) {
                    $shipping_fee_total += $amount;
                }
            }
            $calc_total_num = $base_total_num;
            $orig_ship      = 0.0;
            $orig_ship_tax  = 0.0;
            $multi_ship     = 0.0;
            $multi_ship_tax = 0.0;
            $cart_item_count = 0;
            if ($delivery_option === 'pickup') {
                $calc_total_num = $base_total_num - $shipping_total_num - $shipping_tax_total;
            } elseif ($is_cart_split && $multiship_data) {
                $cart_item_count = isset($multiship_data['cart_item_count']) ? (int)$multiship_data['cart_item_count'] : 0;
                $orig_ship       = isset($multiship_data['original_shipping']) ? (float)$multiship_data['original_shipping'] : 0.0;
                $orig_ship_tax   = isset($multiship_data['original_shipping_tax']) ? (float)$multiship_data['original_shipping_tax'] : 0.0;
                $multi_ship      = isset($multiship_data['multiplied_shipping']) ? (float)$multiship_data['multiplied_shipping'] : 0.0;
                $multi_ship_tax  = isset($multiship_data['multiplied_shipping_tax']) ? (float)$multiship_data['multiplied_shipping_tax'] : 0.0;
                $calc_total_num  = $base_total_num - $orig_ship - $orig_ship_tax + $multi_ship + $multi_ship_tax;
            }
            ?>
            <input type="hidden" name="afb_summary[subtotal]" value="<?php echo esc_attr($subtotal_num); ?>">
            <input type="hidden" name="afb_summary[fees_total]" value="<?php echo esc_attr($fees_total_num); ?>">
            <input type="hidden" name="afb_summary[shipping_total]" value="<?php echo esc_attr($shipping_total_num); ?>">
            <input type="hidden" name="afb_summary[shipping_tax_total]" value="<?php echo esc_attr($shipping_tax_total); ?>">
            <input type="hidden" name="afb_summary[tax_total]" value="<?php echo esc_attr($tax_total_num); ?>">
            <input type="hidden" name="afb_summary[shipping_fee_total]" value="<?php echo esc_attr($shipping_fee_total); ?>">
            <input type="hidden" name="afb_summary[base_total]" value="<?php echo esc_attr($base_total_num); ?>">
            <input type="hidden" name="afb_summary[total]" value="<?php echo esc_attr($calc_total_num); ?>">
            <input type="hidden" name="afb_summary[delivery_option]" value="<?php echo esc_attr($delivery_option); ?>">
            <input type="hidden" name="afb_summary[is_cart_split]" value="<?php echo esc_attr($is_cart_split ? 1 : 0); ?>">
            <input type="hidden" name="afb_summary[cart_item_count]" value="<?php echo esc_attr($cart_item_count); ?>">
            <input type="hidden" name="afb_summary[original_shipping]" value="<?php echo esc_attr($orig_ship); ?>">
            <input type="hidden" name="afb_summary[original_shipping_tax]" value="<?php echo esc_attr($orig_ship_tax); ?>">
            <input type="hidden" name="afb_summary[multiplied_shipping]" value="<?php echo esc_attr($multi_ship); ?>">
            <input type="hidden" name="afb_summary[multiplied_shipping_tax]" value="<?php echo esc_attr($multi_ship_tax); ?>">
        <?php endif; ?>
        
        <hr>
    </div>
    
    
    <?php
    return ob_get_clean();
}

/**
 * AJAX handler for updating order review
 */
function afb_update_order_review_ajax() {
    // Verify nonce for security
    if (!wp_verify_nonce($_POST['nonce'], 'afb_checkout_nonce')) {
        wp_send_json_error('Security check failed');
        wp_die();
    }

    try {
        // Make sure WooCommerce cart is loaded
        if (!WC()->cart) {
            WC()->frontend_includes();
            WC()->session = new WC_Session_Handler();
            WC()->session->init();
            WC()->customer = new WC_Customer(get_current_user_id(), true);
            WC()->cart = new WC_Cart();
            WC()->cart->get_cart();
        }

        // Calculate totals
        WC()->cart->calculate_totals();

        // Get cart hash for fragments
        $cart_hash = WC()->cart->get_cart_hash();

        // Return updated content
        wp_send_json_success([
            'content' => afb_render_order_review_content(),
            'cart_count' => WC()->cart->get_cart_contents_count(),
            'cart_total' => WC()->cart->get_total(),
            'cart_subtotal' => WC()->cart->get_cart_subtotal(),
            'cart_hash' => $cart_hash,
            'fragments' => apply_filters('woocommerce_add_to_cart_fragments', [])
        ]);

    } catch (Exception $e) {
        wp_send_json_error('Error updating order review: ' . $e->getMessage());
    }
}
add_action('wp_ajax_afb_update_order_review', 'afb_update_order_review_ajax');
add_action('wp_ajax_nopriv_afb_update_order_review', 'afb_update_order_review_ajax');

/**
 * AJAX handler for quantity updates
 */
function afb_update_cart_quantity_ajax() {
    // Verify nonce for security
    if (!wp_verify_nonce($_POST['nonce'], 'afb_checkout_nonce')) {
        wp_send_json_error('Security check failed');
        wp_die();
    }

    $cart_key = sanitize_text_field($_POST['cart_key']);
    $quantity = absint($_POST['quantity']);

    if (empty($cart_key)) {
        wp_send_json_error('Invalid cart key');
        wp_die();
    }

    try {
        // Make sure cart is loaded
        if (!WC()->cart) {
            WC()->frontend_includes();
            WC()->session = new WC_Session_Handler();
            WC()->session->init();
            WC()->customer = new WC_Customer(get_current_user_id(), true);
            WC()->cart = new WC_Cart();
            WC()->cart->get_cart();
        }

        // Update quantity or remove item
        if ($quantity == 0) {
            $removed = WC()->cart->remove_cart_item($cart_key);
            if (!$removed) {
                wp_send_json_error('Failed to remove item from cart');
                wp_die();
            }
        } else {
            $updated = WC()->cart->set_quantity($cart_key, $quantity);
            if (!$updated) {
                wp_send_json_error('Failed to update cart quantity');
                wp_die();
            }
        }

        // Calculate totals
        WC()->cart->calculate_totals();

        // Get updated cart hash
        $cart_hash = WC()->cart->get_cart_hash();

        // Return success response
        wp_send_json_success([
            'content' => afb_render_order_review_content(),
            'cart_count' => WC()->cart->get_cart_contents_count(),
            'cart_total' => WC()->cart->get_total(),
            'cart_subtotal' => WC()->cart->get_cart_subtotal(),
            'cart_hash' => $cart_hash,
            'fragments' => apply_filters('woocommerce_add_to_cart_fragments', [])
        ]);

    } catch (Exception $e) {
        wp_send_json_error('Error updating cart quantity: ' . $e->getMessage());
    }
}
add_action('wp_ajax_afb_update_cart_quantity', 'afb_update_cart_quantity_ajax');
add_action('wp_ajax_nopriv_afb_update_cart_quantity', 'afb_update_cart_quantity_ajax');

/**
 * AJAX handler for removing items
 */
function afb_remove_cart_item_ajax() {
    // Verify nonce for security
    if (!wp_verify_nonce($_POST['nonce'], 'afb_checkout_nonce')) {
        wp_send_json_error('Security check failed');
        wp_die();
    }

    $cart_key = sanitize_text_field($_POST['cart_key']);

    if (empty($cart_key)) {
        wp_send_json_error('Invalid cart key');
        wp_die();
    }

    try {
        // Make sure cart is loaded
        if (!WC()->cart) {
            WC()->frontend_includes();
            WC()->session = new WC_Session_Handler();
            WC()->session->init();
            WC()->customer = new WC_Customer(get_current_user_id(), true);
            WC()->cart = new WC_Cart();
            WC()->cart->get_cart();
        }

        // Remove the item
        if (WC()->cart->remove_cart_item($cart_key)) {
            // Calculate totals
            WC()->cart->calculate_totals();

            // Get updated cart hash
            $cart_hash = WC()->cart->get_cart_hash();
            
            wp_send_json_success([
                'content' => afb_render_order_review_content(),
                'cart_count' => WC()->cart->get_cart_contents_count(),
                'cart_total' => WC()->cart->get_total(),
                'cart_subtotal' => WC()->cart->get_cart_subtotal(),
                'cart_hash' => $cart_hash,
                'fragments' => apply_filters('woocommerce_add_to_cart_fragments', [])
            ]);
        } else {
            wp_send_json_error('Failed to remove item from cart');
        }

    } catch (Exception $e) {
        wp_send_json_error('Error removing cart item: ' . $e->getMessage());
    }
}
add_action('wp_ajax_afb_remove_cart_item', 'afb_remove_cart_item_ajax');
add_action('wp_ajax_nopriv_afb_remove_cart_item', 'afb_remove_cart_item_ajax');

/**
 * Enqueue scripts and styles
 */
function afb_enqueue_checkout_scripts() {
    // Only load on pages where you show the popup (adjust conditions as needed)
    if (is_admin()) {
        return;
    }
    
    // Enqueue your JavaScript file (adjust path to match your structure)
    wp_enqueue_script(
        'afb-dynamic-checkout',
        plugin_dir_url(__FILE__) . '../assets/js/afb-dynamic-checkout.js', // Adjust this path
        array('jquery'),
        '1.0.1', // Version
        true
    );
    
    // Localize script with AJAX URL and nonce
    wp_localize_script('afb-dynamic-checkout', 'afb_checkout_vars', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('afb_checkout_nonce'),
        'i18n' => array(
            'removing_item' => __('Suppression en cours...', 'afb-offcanvas'),
            'updating_cart' => __('Mise à jour...', 'afb-offcanvas'),
            'error_removing' => __('Erreur lors de la suppression', 'afb-offcanvas'),
            'error_updating' => __('Erreur lors de la mise à jour', 'afb-offcanvas'),
            'empty_cart' => __('Votre panier est vide', 'afb-offcanvas'),
        )
    ));


    // Make non-checkout pages behave like checkout so core scripts load
    add_filter('woocommerce_is_checkout','__return_true');

    // Enqueue WooCommerce core checkout assets
    if ( function_exists('wp_enqueue_script') ) {
        wp_enqueue_script('wc-checkout');
        wp_enqueue_script('wc-address-i18n');
        wp_enqueue_script('wc-country-select');
        wp_enqueue_script('wc-credit-card-form');
        wp_enqueue_style('woocommerce-inline');
    }

    // Force gateway payment scripts (e.g., Stripe, Tranzila) to register on non-checkout pages
    if ( function_exists('WC') && WC()->payment_gateways() ) {
        $gateways = WC()->payment_gateways->get_available_payment_gateways();
        if ( is_array($gateways) ) {
            foreach ( $gateways as $gw ) {
                if ( method_exists( $gw, 'payment_scripts' ) ) {
                    $gw->payment_scripts();
                }
                if ( method_exists( $gw, 'wp_enqueue_scripts' ) ) {
                    // Some gateways do enqueues here
                    $gw->wp_enqueue_scripts();
                }
            }
        }
    }

}

add_action('wp_enqueue_scripts', 'afb_enqueue_checkout_scripts');

/**
 * Add basic CSS for loading states and UI elements
 */
function afb_checkout_styles() {
    ?>
    <style>
    .afb-order-review.updating {
        opacity: 0.7;
        pointer-events: none;
    }
    
    /* Loading states */
    .afb-order-loading {
        text-align: center;
        padding: 20px;
        color: #666;
        background: rgba(255, 255, 255, 0.8);
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 10;
    }
		
		.afb-back-button:hover,
		.afb-back-button:focus{
			background: #232323 !important;
			border: 1px solid #232323;
			color: white !important;
		}
		
		
		.afb-quantity button{
			width: 28px;
			height: 28px;
			border: 0;
			background: transparent;
			font-size: 22px;
			line-height: 28px;
			cursor: pointer;
			color: var(--afb-text);
		}
    
    .afb-order-review {
        position: relative;
    }
    
    .afb-order-item.updating {
        opacity: 0.6;
        pointer-events: none;
        transition: opacity 0.3s ease;
    }
    
    .afb-order-item.removing {
        opacity: 0.3;
        pointer-events: none;
        transition: opacity 0.3s ease;
        transform: scale(0.95);
    }
    
    /* Quantity input styling */
    .afb-quantity-input[type="number"] {
        -moz-appearance: textfield;
        text-align: center;
        width: 26px;
        border: none;
        padding: 8px 4px;
        background: white;
        font-size: 14px;
    }
    
    .afb-quantity-input::-webkit-outer-spin-button,
    .afb-quantity-input::-webkit-inner-spin-button {
        -webkit-appearance: none;
        margin: 0;
    }
    
    .afb-quantity-input:focus {
        outline: 1px solid #007cba;
    }
    
    /* Quantity control styling */
    .afb-quantity {
        display: flex;
        align-items: center;
/*         border: 1px solid #ddd; */
        border-radius: 4px;
        overflow: hidden;
        max-width: 120px;
    }
		
		.afb-quantity input{
			border-bottom: 0px !important;
			font-weight: 600;
    		font-size: 15px;
		}
    
    .afb-quantity-minus,
    .afb-quantity-plus {
        background: #f8f8f8;
        border: none;
        padding: 8px 12px;
        cursor: pointer;
        font-weight: bold;
        min-width: 32px;
        font-size: 14px;
        transition: background-color 0.2s ease;
    }
    
    .afb-quantity-minus:hover,
    .afb-quantity-plus:hover {
        background: transparent;
    }
    
    .afb-quantity-minus:active,
    .afb-quantity-plus:active {
        background: transparent;
    }
    
    .afb-quantity-minus:disabled,
    .afb-quantity-plus:disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }
    
    /* Remove button styling */
    .afb-order-item-remove a {
        color: black;
        text-decoration: none;
        opacity: 0.7;
        transition: opacity 0.3s ease, transform 0.2s ease;
        display: inline-block; 
    }
    
    .afb-order-item-remove a:hover {
        opacity: 1;
        transform: scale(1.1);
    }
    
    .afb-order-item-remove a:active {
        transform: scale(0.95);
    }
    
    /* Empty cart styling */
    .afb-order-empty {
        text-align: center;
        padding: 40px 20px;
        color: #666;
        font-style: italic;
    }
    
    /* Order item animations */
    .afb-order-item {
        transition: all 0.3s ease;
    }
    
    .afb-order-item:hover {
        background-color: rgba(0, 0, 0, 0.02);
    }
    
    /* Responsive adjustments */
    @media (max-width: 768px) {
        .afb-quantity {
            max-width: 100px;
        }
        
        .afb-quantity-minus,
        .afb-quantity-plus {
            padding: 6px 8px;
            min-width: 28px;
            font-size: 12px;
        }
        
        .afb-quantity-input[type="number"] {
            width: 15px;
            font-size: 12px;
        }
    }
    </style>
    <?php
}
add_action('wp_head', 'afb_checkout_styles');

/**
 * Make sure cart is available in AJAX requests
 */
function afb_init_cart_for_ajax() {
    if (defined('DOING_AJAX') && DOING_AJAX) {
        // Initialize WooCommerce cart for AJAX requests
        if (!WC()->cart) {
            WC()->frontend_includes();
            if (WC()->session && !WC()->session->has_session()) {
                WC()->session->set_customer_session_cookie(true);
            }
            WC()->cart = new WC_Cart();
            WC()->cart->get_cart();
        }
    }
}
add_action('init', 'afb_init_cart_for_ajax', 5);

/**
 * Optional: Hook into WooCommerce cart actions to trigger updates
 */
function afb_trigger_checkout_update() {
    if (!wp_doing_ajax()) {
        ?>
        <script>
        if (typeof jQuery !== 'undefined') {
            console.log('AFB: Triggering checkout update from PHP');
            jQuery(document.body).trigger('update_checkout');
            jQuery(document.body).trigger('afb_update_checkout');
            
            // Also try the global function if available
            if (typeof window.afbUpdateCheckout === 'function') {
                window.afbUpdateCheckout();
            }
        }
        </script>
        <?php
    }
}

// Function to manually trigger update (call this from anywhere in PHP)
function afb_manual_update_trigger() {
    ?>
    <script>
    jQuery(document).ready(function($) {
        console.log('AFB: Manual trigger from PHP');
        $(document.body).trigger('afb_update_checkout');
        if (typeof window.afbUpdateCheckout === 'function') {
            setTimeout(function() {
                window.afbUpdateCheckout();
            }, 100);
        }
    });
    </script>
    <?php
}

// Trigger update when cart is updated via other methods
if (function_exists('add_action')) {
    add_action('woocommerce_cart_item_removed', 'afb_trigger_checkout_update');
    add_action('woocommerce_cart_item_restored', 'afb_trigger_checkout_update');
    add_action('woocommerce_cart_item_set_quantity', 'afb_trigger_checkout_update');
}

/**
 * Add cart fragments for compatibility with other plugins
 */
function afb_add_cart_fragments($fragments) {
    // Don't use fragments for this implementation since we're replacing content manually
    return $fragments;
}
if (function_exists('add_filter')) {
    add_filter('woocommerce_add_to_cart_fragments', 'afb_add_cart_fragments');
}

/**
 * Debug function (remove in production)
 */
function afb_debug_cart() {
    if (defined('WP_DEBUG') && WP_DEBUG && current_user_can('manage_options')) {
        error_log('AFB Cart Debug - Items: ' . WC()->cart->get_cart_contents_count());
        error_log('AFB Cart Debug - Total: ' . WC()->cart->get_total());
    }
}

