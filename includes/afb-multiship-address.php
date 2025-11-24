<?php
/**
 * Multiship Address Handler
 * Saves selected address alias per order item and exposes it in admin
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Use WordPress core sanitization
if (!function_exists('afb_esc_html')) {
    function afb_esc_html($text) {
        if (function_exists('esc_html')) {
            // Indirect call to avoid static analyzer undefined-function warnings
            return call_user_func('esc_html', $text);
        }
        return htmlspecialchars((string)$text, ENT_QUOTES, 'UTF-8');
    }
}

// Save selected address alias per order item during checkout item creation
if (function_exists('add_action')) {
    call_user_func('add_action', 'woocommerce_checkout_create_order_line_item', function ($item, $cart_item_key, $values, $order) {
        if (!isset($_POST['multi_shipping_adr_data'])) {
            return;
        }

        $raw = $_POST['multi_shipping_adr_data'];
        // Handle JSON delivered as string (with possible slashes) or array
        $data = is_string($raw) ? json_decode(stripslashes($raw), true) : $raw;
        if (!is_array($data)) {
            return;
        }

        if (isset($data[$cart_item_key]) && is_array($data[$cart_item_key])) {
            $entry = $data[$cart_item_key];
            $alias_key = isset($entry['address_name']) ? sanitize_text_field($entry['address_name']) : '';
            if ($alias_key !== '' && method_exists($item, 'add_meta_data')) {
                // Resolve full address array from user's custom addresses meta
                $customer_id = (method_exists($order, 'get_customer_id')) ? (int) $order->get_customer_id() : 0;
                if ($customer_id <= 0) {
                    $customer_id = get_current_user_id();
                }
                $addr_meta = $customer_id ? get_user_meta($customer_id, 'thwma_custom_address', true) : [];
                if (is_string($addr_meta)) { $addr_meta = maybe_unserialize($addr_meta); }
                $shipping_map = (is_array($addr_meta) && isset($addr_meta['shipping']) && is_array($addr_meta['shipping'])) ? $addr_meta['shipping'] : [];
                $address_data = (is_array($shipping_map) && isset($shipping_map[$alias_key]) && is_array($shipping_map[$alias_key])) ? $shipping_map[$alias_key] : null;

                // Save the full address array when available; fallback to key if not
                if (is_array($address_data)) {
                    $item->add_meta_data('selected_address', $address_data, true);
                } else {
                    $item->add_meta_data('selected_address', $alias_key, true);
                }
                // Also store the key for reference/debug
                $item->add_meta_data('selected_address_key', $alias_key, true);
            }
        }
    }, 10, 4);
}

// Optional admin summary section (addresses will also appear under each item meta)
if (function_exists('add_action')) {
    call_user_func('add_action', 'woocommerce_admin_order_data_after_order_items', function ($order) {
        if (!$order || !method_exists($order, 'get_items')) {
            return;
        }

        $items = $order->get_items();
        $has_any = false;
        echo '<div class="order-addresses">';
        echo '<h3>' . afb_esc_html(__('Adresses de livraison sélectionnées', 'afb-offcanvas')) . '</h3>';
        echo '<table class="widefat">';
        echo '<thead><tr><th>' . afb_esc_html(__('Produit', 'afb-offcanvas')) . '</th><th>' . afb_esc_html(__('Adresse', 'afb-offcanvas')) . '</th></tr></thead>';
        echo '<tbody>';
        foreach ($items as $item_id => $item) {
            $name  = method_exists($item, 'get_name') ? $item->get_name() : 'Item';
            $address_meta = method_exists($item, 'get_meta') ? $item->get_meta('selected_address', true) : '';
            $alias_key    = method_exists($item, 'get_meta') ? (string)$item->get_meta('selected_address_key', true) : '';

            // If saved meta is a string (legacy), try to resolve to array via order customer
            if (!is_array($address_meta) && $alias_key !== '') {
                $customer_id = method_exists($order, 'get_customer_id') ? (int)$order->get_customer_id() : 0;
                $addr_meta = $customer_id ? get_user_meta($customer_id, 'thwma_custom_address', true) : [];
                if (is_string($addr_meta)) { $addr_meta = maybe_unserialize($addr_meta); }
                $shipping_map = (is_array($addr_meta) && isset($addr_meta['shipping']) && is_array($addr_meta['shipping'])) ? $addr_meta['shipping'] : [];
                if (isset($shipping_map[$alias_key]) && is_array($shipping_map[$alias_key])) {
                    $address_meta = $shipping_map[$alias_key];
                }
            }

            echo '<tr>';
            echo '<td>' . afb_esc_html($name) . '</td>';
            echo '<td>';
            if (is_array($address_meta)) {
                // Render mini table of address fields
                echo '<table class="wp-list-table widefat fixed striped afb-mini-address">';
                $labels = [
                    'shipping_first_name' => __('Prénom', 'afb-offcanvas'),
                    'shipping_last_name'  => __('Nom', 'afb-offcanvas'),
                    'shipping_company'    => __('Entreprise', 'afb-offcanvas'),
                    'shipping_country'    => __('Pays', 'afb-offcanvas'),
                    'shipping_address_1'  => __('Adresse 1', 'afb-offcanvas'),
                    'shipping_address_2'  => __('Adresse 2', 'afb-offcanvas'),
                    'shipping_city'       => __('Ville', 'afb-offcanvas'),
                    'shipping_state'      => __('État', 'afb-offcanvas'),
                    'shipping_postcode'   => __('Code postal', 'afb-offcanvas'),
                ];
                foreach ($labels as $k => $label) {
                    $val = isset($address_meta[$k]) ? (string)$address_meta[$k] : '';
                    if ($val !== '') {
                        echo '<tr><th style="width:160px;">' . afb_esc_html($label) . '</th><td>' . afb_esc_html($val) . '</td></tr>';
                    }
                }
                echo '</table>';
                $has_any = true;
            } else {
                // Fallback: show alias key
                echo afb_esc_html($alias_key !== '' ? $alias_key : (string)$address_meta);
                $has_any = true;
            }
            echo '</td>';
            echo '</tr>';
        }
        echo '</tbody></table>';
        echo '</div>';
    }, 10, 1);
}

// Unified admin display handled in includes/afb-order-itemmeta-display.php using
// the 'woocommerce_after_order_itemmeta' hook to avoid duplication under item name.