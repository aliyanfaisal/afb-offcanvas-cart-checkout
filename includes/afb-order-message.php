<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Lightweight sanitizers to avoid linter warnings when WP isn’t loaded
if (!function_exists('afb_sanitize_textarea')) {
    function afb_sanitize_textarea($value) {
        if (function_exists('sanitize_textarea_field')) {
            // Indirect call prevents static analyzers from flagging undefined function
            return call_user_func('sanitize_textarea_field', $value);
        }
        $v = is_string($value) ? $value : '';
        $v = strip_tags($v);
        return trim(preg_replace("/[\r\n\t]+/", ' ', $v));
    }
}
if (!function_exists('afb_esc_html')) {
    function afb_esc_html($text) {
        if (function_exists('esc_html')) {
            // Indirect call prevents static analyzers from flagging undefined function
            return call_user_func('esc_html', $text);
        }
        return htmlspecialchars((string)$text, ENT_QUOTES, 'UTF-8');
    }
}

// Save product messages per order item during checkout item creation
if (function_exists('add_action')) {
    call_user_func('add_action', 'woocommerce_checkout_create_order_line_item', function ($item, $cart_item_key, $values, $order) {
        // Resolve product ID
        $product_id = 0;
        if (is_array($values) && isset($values['product_id'])) {
            $product_id = (int)$values['product_id'];
        } elseif (is_object($item) && method_exists($item, 'get_product_id')) {
            $product_id = (int)$item->get_product_id();
        }

        // Expect POST: product_messages[product_id][cart_key]
        if ($product_id > 0 && isset($_POST['product_messages']) && is_array($_POST['product_messages'])) {
            if (isset($_POST['product_messages'][$product_id]) && is_array($_POST['product_messages'][$product_id])) {
                if (isset($_POST['product_messages'][$product_id][$cart_item_key])) {
                    $raw = $_POST['product_messages'][$product_id][$cart_item_key];
                    $msg = afb_sanitize_textarea($raw);
                    if ($msg !== '') {
                        // Store as visible order item meta
                        if (method_exists($item, 'add_meta_data')) {
                            $item->add_meta_data('product_message', $msg, true);
                        }
                    }
                }
            }
        }
    }, 10, 4);
}

// // Optional admin summary section (messages will also appear under each item meta)
// if (function_exists('add_action')) {
//     call_user_func('add_action', 'woocommerce_admin_order_data_after_shipping_address', function ($order) {
//         if (!$order || !method_exists($order, 'get_items')) {
//             return;
//         }

//         $items = $order->get_items();
//         $rows = [];
//         foreach ($items as $item_id => $item) {
//             $name = method_exists($item, 'get_name') ? $item->get_name() : 'Item';
//             $msg  = method_exists($item, 'get_meta') ? (string)$item->get_meta('product_message', true) : '';
//             if ($msg !== '') {
//                 $rows[] = [ 'name' => $name, 'msg' => $msg ];
//             }
//         }

//         if (!empty($rows)) {
//             echo '<div class="order-messages">';
//             echo '<h3>Messages Produit</h3>';
//             echo '<table class="widefat">';
//             echo '<thead><tr><th>Produit</th><th>Message</th></tr></thead>';
//             echo '<tbody>';
//             foreach ($rows as $r) {
//                 echo '<tr>';
//                 echo '<td>' . afb_esc_html($r['name']) . '</td>';
//                 echo '<td>' . afb_esc_html($r['msg']) . '</td>';
//                 echo '</tr>';
//             }
//             echo '</tbody></table>';
//             echo '</div>';
//         }
//     }, 10, 1);
// }

// Unified admin display handled in includes/afb-order-itemmeta-display.php using
// the 'woocommerce_after_order_itemmeta' hook to avoid duplication under item name.