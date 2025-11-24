<?php
/**
 * Multiship Phone Number Handler
 * Saves and displays phone numbers per order item
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

// Save product phone per order item during checkout item creation
if (function_exists('add_action')) {
    call_user_func('add_action', 'woocommerce_checkout_create_order_line_item', function ($item, $cart_item_key, $values, $order) {
        $product_id = 0;
        if (is_array($values) && isset($values['product_id'])) {
            $product_id = (int)$values['product_id'];
        } elseif (is_object($item) && method_exists($item, 'get_product_id')) {
            $product_id = (int)$item->get_product_id();
        }

        if ($product_id > 0) {
            $phone = '';
            // Support bracket array format: product_phones[product_id][cart_key]
            if (isset($_POST['product_phones']) && is_array($_POST['product_phones'])) {
                if (isset($_POST['product_phones'][$product_id]) && is_array($_POST['product_phones'][$product_id])) {
                    if (isset($_POST['product_phones'][$product_id][$cart_item_key]) && $_POST['product_phones'][$product_id][$cart_item_key] !== '') {
                        $phone = (string)$_POST['product_phones'][$product_id][$cart_item_key];
                    }
                }
            }
            // Fallback to flat key: product_phone_PRODUCTID_CARTKEY
            if ($phone === '') {
                $flat_key = 'product_phone_' . $product_id . '_' . $cart_item_key;
                if (isset($_POST[$flat_key]) && $_POST[$flat_key] !== '') {
                    $phone = (string)$_POST[$flat_key];
                }
            }

            if ($phone !== '') {
                $phone = sanitize_text_field($phone);
                if ($phone !== '' && method_exists($item, 'add_meta_data')) {
                    $item->add_meta_data('product_phone', $phone, true);
                }
            }
        }
    }, 10, 4);
}

// // Optional admin summary section (phones will also appear under each item meta)
// if (function_exists('add_action')) {
//     call_user_func('add_action', 'woocommerce_admin_order_data_after_billing_address', function ($order) {
//         if (!$order || !method_exists($order, 'get_items')) {
//             return;
//         }

//         $items = $order->get_items();
//         $rows = [];
//         foreach ($items as $item_id => $item) {
//             $name  = method_exists($item, 'get_name') ? $item->get_name() : 'Item';
//             $phone = method_exists($item, 'get_meta') ? (string)$item->get_meta('product_phone', true) : '';
//             if ($phone !== '') {
//                 $rows[] = [ 'name' => $name, 'phone' => $phone ];
//             }
//         }

//         if (!empty($rows)) {
//             echo '<div class="order-phones">';
//             echo '<h3>Numéros de téléphone des produits</h3>';
//             echo '<table class="wp-list-table widefat fixed striped">';
//             echo '<thead><tr><th>Produit</th><th>Numéro de téléphone</th></tr></thead>';
//             echo '<tbody>';
//             foreach ($rows as $r) {
//                 echo '<tr>';
//                 echo '<td>' . afb_esc_html($r['name']) . '</td>';
//                 echo '<td>' . afb_esc_html($r['phone']) . '</td>';
//                 echo '</tr>';
//             }
//             echo '</tbody>';
//             echo '</table>';
//             echo '</div>';
//         }
//     }, 10, 1);
// }

// Unified admin display handled in includes/afb-order-itemmeta-display.php using
// the 'woocommerce_after_order_itemmeta' hook to avoid duplication under item name.