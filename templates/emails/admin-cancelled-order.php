<?php
if (!defined('ABSPATH')) { exit; }

do_action('woocommerce_email_header', $email_heading, $email);

if (!$plain_text) {
    echo '<p style="margin:0 0 16px; font-family: \"Helvetica Neue\", Helvetica, Roboto, Arial, sans-serif;">' . esc_html__('An order was cancelled. Details below:', 'woocommerce') . '</p>';
}

if ($plain_text) {
    foreach ($order->get_items() as $item_id => $item) {
        if (!is_object($item) || (method_exists($item, 'get_type') && $item->get_type() !== 'line_item')) { continue; }
        $name = (string) (method_exists($item, 'get_name') ? $item->get_name() : '');
        $qty  = (int) (method_exists($item, 'get_quantity') ? $item->get_quantity() : 1);
        if ($qty <= 0) { $qty = 1; }
        $line_total = (float) (method_exists($item, 'get_total') ? $item->get_total() : 0.0);
        $line_tax   = (float) (method_exists($item, 'get_total_tax') ? $item->get_total_tax() : 0.0);
        $unit_inc   = ($qty > 0) ? (($line_total + $line_tax) / $qty) : ($line_total + $line_tax);
        $unit_str   = strip_tags(wc_price($unit_inc, ['currency' => $order->get_currency()]));
        $line_str   = strip_tags(wc_price($line_total + $line_tax, ['currency' => $order->get_currency()]));
        echo '* ' . $name . ' - ' . $unit_str . ' x ' . $qty . ' = ' . $line_str . "\n";
        do_action('woocommerce_order_item_meta_end', $item_id, $item, $order, true);
    }
} else {
    echo '<div id="body_content_inner_cell" style="padding: 20px 32px 32px;">';
    echo '<div id="body_content_inner" style="color: #4b4b4b; font-family: \"Helvetica Neue\",Helvetica,Roboto,Arial,sans-serif; font-size: 16px; line-height: 150%; text-align: left;' . '" align="left">';

    echo '<h2 class="email-order-detail-heading" style="color: #1e1e1e; display: block; font-family: \"Helvetica Neue\",Helvetica,Roboto,Arial,sans-serif; font-size: 20px; font-weight: bold; line-height: 160%; margin: 0 0 18px; text-align: left;">'
        . esc_html__('Order summary', 'woocommerce')
        . '<br><span style="color: #b7c4ca; display: block; font-size: 14px; font-weight: normal;">'
        . '<a class="link" href="' . esc_url( admin_url( 'admin.php?page=wc-orders&action=edit&id=' . $order->get_id() ) ) . '" style="font-weight: normal; text-decoration: underline; color: #b7c4ca; font-family: \"Helvetica Neue\",Helvetica,Roboto,Arial,sans-serif;">' . sprintf( esc_html__('Order #%d', 'woocommerce'), $order->get_id() ) . '</a> '
        . esc_html( date_i18n( 'j F Y', $order->get_date_created() ? $order->get_date_created()->getTimestamp() : time() ) )
        . '</span></h2>';

    echo '<div style="margin-bottom: 24px;">';
    echo '<table class="td font-family email-order-details" cellspacing="0" cellpadding="6" border="0" style="color: #4b4b4b; border-bottom: 1px solid rgba(0, 0, 0, .2); vertical-align: middle; font-family: \"Helvetica Neue\",Helvetica,Roboto,Arial,sans-serif; width: 100%;" width="100%">';
    echo '<tbody>';

    foreach ($order->get_items() as $item_id => $item) {
        if (!is_object($item) || (method_exists($item, 'get_type') && $item->get_type() !== 'line_item')) { continue; }
        $product = method_exists($item, 'get_product') ? $item->get_product() : null;
        $name = (string) (method_exists($item, 'get_name') ? $item->get_name() : '');
        $qty  = (int) (method_exists($item, 'get_quantity') ? $item->get_quantity() : 1);
        if ($qty <= 0) { $qty = 1; }
        $line_total = (float) (method_exists($item, 'get_total') ? $item->get_total() : 0.0);
        $line_tax   = (float) (method_exists($item, 'get_total_tax') ? $item->get_total_tax() : 0.0);
        $unit_inc   = ($qty > 0) ? (($line_total + $line_tax) / $qty) : ($line_total + $line_tax);
        $unit_html  = wc_price($unit_inc, ['currency' => $order->get_currency()]);
        $line_html  = wc_price($line_total + $line_tax, ['currency' => $order->get_currency()]);

        $thumb_url = '';
        $alt_text  = $name;
        if ($product && method_exists($product, 'get_image_id')) {
            $img_id = (int) $product->get_image_id();
            if ($img_id) {
                $src = wp_get_attachment_image_src($img_id, [100, 100]);
                if (is_array($src) && !empty($src[0])) { $thumb_url = $src[0]; }
            }
        }

        echo '<tr class="email-order-details-item">';
        echo '<td class="td" style="color: #4b4b4b; font-family: \"Helvetica Neue\",Helvetica,Roboto,Arial,sans-serif; border-bottom: 1px solid rgba(0,0,0,.2); vertical-align: middle; padding: 8px 12px;">';
        echo '<table><tbody><tr>';
        echo '<td style="vertical-align: middle; padding-right: 24px;">';
        if ($thumb_url !== '') {
            echo '<img src="' . esc_url($thumb_url) . '" alt="' . esc_attr($alt_text) . '" class="attachment-48x48 size-48x48" style="border:none; display:inline-block; font-size:14px; font-weight:bold; height:auto; outline:none; text-decoration:none; text-transform:capitalize; vertical-align:middle; margin-right:24px; max-width:100%;" />';
        }
        echo '</td>';
        echo '<td style="vertical-align: middle; padding-right: 0;">';
        echo '<div style="font-weight:bold;">' . esc_html($name) . '</div>';
        ob_start();
        do_action('woocommerce_order_item_meta_end', $item_id, $item, $order, false);
        $meta_html = ob_get_clean();
        if (!empty($meta_html)) { echo $meta_html; }
        echo '</td>';
        echo '</tr></tbody></table>';
        echo '</td>';
        echo '<td class="td font-family text-align-right" style="color: #4b4b4b; border-bottom: 1px solid rgba(0, 0, 0, .2); font-family: \"Helvetica Neue\",Helvetica,Roboto,Arial,sans-serif; text-align: right; padding: 8px 12px; vertical-align: middle;" align="right">';
        echo '<span class="woocommerce-Price-amount amount">' . wp_kses_post($unit_html) . ' × ' . esc_html($qty) . '</span>';
        echo '</td>';
        echo '<td class="td font-family text-align-right" style="color: #4b4b4b; border-bottom: 1px solid rgba(0, 0, 0, .2); font-family: \"Helvetica Neue\",Helvetica,Roboto,Arial,sans-serif; text-align: right; padding: 8px 12px; padding-right: 0; vertical-align: middle;" align="right">';
        echo '<span class="woocommerce-Price-amount amount">' . wp_kses_post($line_html) . '</span>';
        echo '</td>';
        echo '</tr>';
    }

    echo '</tbody>';
    $totals = $order->get_order_item_totals();
    if (is_array($totals) && !empty($totals)) {
        echo '<tfoot>';
        foreach ($totals as $key => $total) {
            $is_total = ($key === 'order_total');
            $row_class = 'order-totals order-totals-' . esc_attr($key) . ($is_total ? ' order-totals-last' : '');
            echo '<tr class="' . $row_class . '">';
            echo '<th class="td text-align-left" scope="row" colspan="2" style="color: #4b4b4b; font-family: \"Helvetica Neue\", Helvetica, Roboto, Arial, sans-serif; border: 0; vertical-align: middle; text-align: left; padding: 8px 12px; ' . ($is_total ? 'font-weight: bold;' : 'font-weight: normal;') . ' padding-bottom: 5px; padding-left: 0; ' . ($key === 'cart_subtotal' ? 'padding-top: 24px; border-top-width: 4px;' : 'padding-top: 5px;') . '" align="left">' . wp_kses_post($total['label']) . '</th>';
            echo '<td class="td text-align-right" style="color: #4b4b4b; font-family: \"Helvetica Neue\", Helvetica, Roboto, Arial, sans-serif; border: 0; vertical-align: middle; text-align: right; padding: 8px 12px; ' . ($is_total ? 'font-weight: bold; font-size: 20px;' : 'font-weight: normal;') . ' padding-bottom: 5px; padding-right: 0; padding-top: 5px;" align="right">' . wp_kses_post($total['value']) . '</td>';
            echo '</tr>';
        }
        echo '</tfoot>';
    }

    echo '</table>';
    echo '</div>';
    echo '</div>';
}

$delivery_option = (string) $order->get_meta('afb_delivery_option');
if ($delivery_option === '') { $delivery_option = (string) get_post_meta($order->get_id(), 'afb_delivery_option', true); }
$pickup_location = (string) $order->get_meta('_pickup_location', true);
if ($pickup_location === '') { $pickup_location = (string) get_post_meta($order->get_id(), '_pickup_location', true); }
if ($delivery_option === 'multiship') {
    if (!$plain_text) {
        // echo '<p style="margin:16px 0 0; color:#666; font-size:12px;">' . esc_html__('Recipient info appears under each item in multi-ship orders.', 'afb-offcanvas') . '</p>';
    }
} elseif ($delivery_option !== 'pickup') {
    $label_map = [
        'pickup'    => __('In-Store Pickup', 'afb-offcanvas'),
        'multiship' => __('Multi-ship', 'afb-offcanvas'),
        'standard'  => __('Standard Delivery', 'afb-offcanvas'),
    ];
    $label = isset($label_map[$delivery_option]) ? $label_map[$delivery_option] : ($delivery_option ?: __('Delivery', 'afb-offcanvas'));
    if ($plain_text) {
        echo "\n" . __('Delivery Option', 'afb-offcanvas') . ': ' . $label . "\n";
    } else {
        echo '<div style="margin:16px 0 0;">'
           . '<h3 style="margin:0 0 6px; font-size:16px; font-family: \"Helvetica Neue\", Helvetica, Roboto, Arial, sans-serif;">' . esc_html__('Delivery Option', 'afb-offcanvas') . '</h3>'
           . '<p style="margin:0; font-family: \"Helvetica Neue\", Helvetica, Roboto, Arial, sans-serif;">' . esc_html($label) . '</p>'
           . '</div>';
    }
}

wc_get_template('emails/email-order-meta.php', [
    'order'         => $order,
    'sent_to_admin' => $sent_to_admin,
    'plain_text'    => $plain_text,
    'email'         => $email,
]);

$hide_shipping = in_array($delivery_option, ['multiship', 'pickup'], true);
$billing_address_html = $order->get_formatted_billing_address();
$shipping_address_html = $order->get_formatted_shipping_address();
$billing_phone = $order->get_billing_phone();
$billing_email = $order->get_billing_email();

if ($plain_text) {
    echo "\n" . __('Billing address', 'woocommerce') . "\n";
    echo wp_strip_all_tags($billing_address_html) . "\n";
    if ($billing_phone) { echo $billing_phone . "\n"; }
    if ($billing_email) { echo $billing_email . "\n"; }
    if ($pickup_location) {
        $info = function_exists('afb_get_store_info') ? afb_get_store_info($pickup_location) : null;
        if ($info && ($info['address'] || $info['city'] || $info['phone'])) {
            echo "\n" . __('In-Store Pickup Info', 'afb-offcanvas') . ":\n";
            echo ($info['name'] ? $info['name'] . "\n" : '');
            echo ($info['address'] ? $info['address'] . "\n" : '');
            echo ($info['city'] ? $info['city'] . "\n" : '');
            echo ($info['phone'] ? $info['phone'] . "\n" : '');
        } else {
            $formatted = function_exists('afb_format_pickup_location') ? afb_format_pickup_location($pickup_location) : $pickup_location;
            echo "\n" . __('In-Store Pickup Info', 'afb-offcanvas') . ': ' . $formatted . "\n";
        }
    } elseif (!$hide_shipping && $shipping_address_html) {
        echo "\n" . __('Shipping address', 'woocommerce') . "\n";
        echo wp_strip_all_tags($shipping_address_html) . "\n";
    }
} else {
    echo '<table id="addresses" cellspacing="0" cellpadding="0" border="0" width="100%" style="width:100%;vertical-align:top;margin-bottom:0;padding:0">';
    echo '<tr>';
    if ($delivery_option === 'pickup' && $pickup_location) {
        echo '<td colspan="2" valign="top" align="right" style="font-family: Helvetica, Arial, sans-serif; text-align:right; border:0; padding:0">';
        echo '<div style="display:grid; grid-template-columns: 1fr 1fr; gap: 12px; align-items:start">';
        echo '<div>';
        echo '<b style="color:#1e1e1e; font-family: Helvetica, Arial, sans-serif">' . esc_html(__('Billing address', 'woocommerce')) . '</b>';
        echo '<address style="color:#1e1e1e; font-style:normal; padding:8px 0; word-break:break-all">';
        echo wp_kses_post($billing_address_html);
        if ($billing_phone) { echo '<br><a href="tel:' . esc_attr($billing_phone) . '" target="_blank" style="color:#b7c4ca; font-weight:normal; text-decoration:underline">' . esc_html($billing_phone) . '</a>'; }
        if ($billing_email) { echo '<br><a href="mailto:' . esc_attr($billing_email) . '" target="_blank" style="color:#b7c4ca; font-weight:normal; text-decoration:underline">' . esc_html($billing_email) . '</a>'; }
        echo '</address>';
        echo '</div>';
        echo '<div>';
        echo '<b style="color:#1e1e1e; font-family: Helvetica, Arial, sans-serif">' . esc_html__('In-Store Pickup Info', 'afb-offcanvas') . '</b>';
        $info = function_exists('afb_get_store_info') ? afb_get_store_info($pickup_location) : null;
        echo '<div style="padding:8px 0;">';
        if ($info && ($info['address'] || $info['city'] || $info['phone'])) {
            echo '<p style="margin:0;">' . esc_html( $info['name'] ) . '</p>';
            if ($info['address']) { echo '<p style="margin:0;">' . esc_html( $info['address'] ) . '</p>'; }
            if ($info['city'])    { echo '<p style="margin:0;">' . esc_html( $info['city'] ) . '</p>'; }
            if ($info['phone'])   { echo '<p style="margin:0;">' . esc_html( $info['phone'] ) . '</p>'; }
        } else {
            echo '<p style="margin:0;">' . esc_html( function_exists('afb_format_pickup_location') ? afb_format_pickup_location($pickup_location) : $pickup_location ) . '</p>';
        }
        echo '</div>';
        echo '</div>';
        echo '</div>';
        echo '</td>';
    } else {
        echo '<td valign="top" width="50%" align="right" style="font-family: Helvetica, Arial, sans-serif; text-align:right; border:0; padding:0">';
        echo '<b style="color:#1e1e1e; font-family: Helvetica, Arial, sans-serif">' . esc_html(__('Billing address', 'woocommerce')) . '</b>';
        echo '<address style="color:#1e1e1e; font-style:normal; padding:8px 0; word-break:break-all">';
        echo wp_kses_post($billing_address_html);
        if ($billing_phone) { echo '<br><a href="tel:' . esc_attr($billing_phone) . '" style="color:#b7c4ca; font-weight:normal; text-decoration:underline" target="_blank">' . esc_html($billing_phone) . '</a>'; }
        if ($billing_email) { echo '<br><a href="mailto:' . esc_attr($billing_email) . '" target="_blank" style="color:#b7c4ca; font-weight:normal; text-decoration:underline">' . esc_html($billing_email) . '</a>'; }
        echo '</address>';
        echo '</td>';

        echo '<td valign="top" width="50%" align="right" style="font-family: Helvetica, Arial, sans-serif; text-align:right; padding:0; padding-right:10px">';
        if (!$hide_shipping && $shipping_address_html) {
            echo '<b style="color:#1e1e1e; font-family: Helvetica, Arial, sans-serif">' . esc_html(__('Shipping address', 'woocommerce')) . '</b>';
            echo '<address style="color:#1e1e1e; font-style:normal; padding:8px 0; word-break:break-all; font-family: Helvetica, Arial, sans-serif">';
            echo wp_kses_post($shipping_address_html);
            echo '</address>';
        }
        echo '</td>';
    }
    echo '</tr>';
    echo '</table>';
}

do_action('woocommerce_email_footer', $email);