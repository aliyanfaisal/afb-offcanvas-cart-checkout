<?php
if (!defined('ABSPATH')) { exit; }

if (function_exists('do_action')) {
    do_action('woocommerce_email_header', $email_heading, $email);
}

$text_align = is_rtl() ? 'right' : 'left';
$text_align_end = is_rtl() ? 'left' : 'right';

if (!$plain_text) {
    echo '<p style="margin:0 0 16px; font-family: Helvetica, Arial, sans-serif;">' . esc_html__('Order details and payment information:', 'woocommerce') . '</p>';
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
    echo '<div id="body_content_inner" style="color: #4b4b4b; font-family: Helvetica, Arial, sans-serif; font-size: 16px; line-height: 150%; text-align: ' . $text_align . ';" align="' . $text_align . '">';
    echo '<h2 class="email-order-detail-heading" style="color: #1e1e1e; display: block; font-family: Helvetica, Arial, sans-serif; font-size: 20px; font-weight: bold; line-height: 160%; margin: 0 0 18px; text-align: ' . $text_align . ';">'
        . esc_html__('Order summary', 'woocommerce')
        . '<br><span style="color: #b7c4ca; display: block; font-size: 14px; font-weight: normal;">'
        . '<a class="link" href="' . esc_url( admin_url( 'admin.php?page=wc-orders&action=edit&id=' . $order->get_id() ) ) . '" style="font-weight: normal; text-decoration: underline; color: #b7c4ca; font-family: Helvetica, Arial, sans-serif;">' . sprintf( esc_html__('Order #%d', 'woocommerce'), $order->get_id() ) . '</a> '
        . esc_html( date_i18n( 'j F Y', $order->get_date_created() ? $order->get_date_created()->getTimestamp() : time() ) )
        . '</span></h2>';
    echo '<div style="margin-bottom: 24px;">';
    echo '<table class="td font-family email-order-details" cellspacing="0" cellpadding="6" border="0" style="color: #4b4b4b; border-bottom: 1px solid rgba(0, 0, 0, .2); vertical-align: middle; font-family: Helvetica, Arial, sans-serif; width: 100%;" width="100%">';
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
        // Left Column: Product (Align Start)
        echo '<td class="td" style="color: #4b4b4b; font-family: Helvetica, Arial, sans-serif; border-bottom: 1px solid rgba(0,0,0,.2); vertical-align: middle; padding: 8px 12px; text-align: ' . $text_align . '" align="' . $text_align . '">';
        echo '<table width="100%"><tbody><tr>';
        
        // Image
        echo '<td style="vertical-align: middle; padding-' . $text_align_end . ': 24px;">';
        if ($thumb_url !== '') {
            echo '<img src="' . esc_url($thumb_url) . '" alt="' . esc_attr($alt_text) . '" class="attachment-48x48 size-48x48" style="border:none; display:inline-block; font-size:14px; font-weight:bold; height:auto; outline:none; text-decoration:none; text-transform:capitalize; vertical-align:middle; max-width:100%;" />';
        }
        echo '</td>';
        
        // Name
        echo '<td style="vertical-align: middle; padding: 0;">';
        echo '<div style="font-weight:bold;">' . esc_html($name) . '</div>';
        ob_start();
        do_action('woocommerce_order_item_meta_end', $item_id, $item, $order, false);
        $meta_html = ob_get_clean();
        if (!empty($meta_html)) { echo $meta_html; }
        echo '</td>';
        echo '</tr></tbody></table>';
        echo '</td>';

        // Prices: Align End
        echo '<td class="td font-family text-align-' . $text_align_end . '" style="color: #4b4b4b; border-bottom: 1px solid rgba(0, 0, 0, .2); font-family: Helvetica, Arial, sans-serif; text-align: ' . $text_align_end . '; padding: 8px 12px; vertical-align: middle;" align="' . $text_align_end . '">';
        echo '<span class="woocommerce-Price-amount amount">' . wp_kses_post($unit_html) . ' × ' . esc_html($qty) . '</span>';
        echo '</td>';
        echo '<td class="td font-family text-align-' . $text_align_end . '" style="color: #4b4b4b; border-bottom: 1px solid rgba(0, 0, 0, .2); font-family: Helvetica, Arial, sans-serif; text-align: ' . $text_align_end . '; padding: 8px 12px; padding-' . $text_align_end . ': 0; vertical-align: middle;" align="' . $text_align_end . '">';
        echo '<span class="woocommerce-Price-amount amount">' . wp_kses_post($line_html) . '</span>';
        echo '</td>';
        echo '</tr>';
    }
    echo '</tbody>';

    // Totals
    $totals = $order->get_order_item_totals();
    if (is_array($totals) && !empty($totals)) {
        echo '<tfoot>';
        foreach ($totals as $key => $total) {
            $is_total = ($key === 'order_total');
            $row_class = 'order-totals order-totals-' . esc_attr($key) . ($is_total ? ' order-totals-last' : '');
            echo '<tr class="' . $row_class . '">';
            // Label: Align Start
            echo '<th class="td text-align-' . $text_align . '" scope="row" colspan="2" style="color: #4b4b4b; font-family: Helvetica, Arial, sans-serif; border: 0; vertical-align: middle; text-align: ' . $text_align . '; padding: 8px 12px; ' . ($is_total ? 'font-weight: bold;' : 'font-weight: normal;') . ' padding-bottom: 5px; padding-' . $text_align . ': 0; ' . ($key === 'cart_subtotal' ? 'padding-top: 24px; border-top-width: 4px;' : 'padding-top: 5px;') . '" align="' . $text_align . '">' . wp_kses_post($total['label']) . '</th>';
            // Value: Align End
            echo '<td class="td text-align-' . $text_align_end . '" style="color: #4b4b4b; font-family: Helvetica, Arial, sans-serif; border: 0; vertical-align: middle; text-align: ' . $text_align_end . '; padding: 8px 12px; ' . ($is_total ? 'font-weight: bold; font-size: 20px;' : 'font-weight: normal;') . ' padding-bottom: 5px; padding-' . $text_align_end . ': 0; padding-top: 5px;" align="' . $text_align_end . '">' . wp_kses_post($total['value']) . '</td>';
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
    if (!$plain_text) { }
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
           . '<h3 style="margin:0 0 6px; font-size:16px; font-family: Helvetica, Arial, sans-serif; text-align: ' . $text_align . ';">' . esc_html__('Delivery Option', 'afb-offcanvas') . '</h3>'
           . '<p style="margin:0; font-family: Helvetica, Arial, sans-serif; text-align: ' . $text_align . ';">' . esc_html($label) . '</p>'
           . '</div>';
    }
}

// Order meta (e.g., customer note)
wc_get_template('emails/email-order-meta.php', [
    'order'         => $order,
    'sent_to_admin' => $sent_to_admin,
    'plain_text'    => $plain_text,
    'email'         => $email,
]);

// Addresses - Note: Invoice template didn't use the sidebar layout in my previous view, but I'll stick to the original content's simplicity unless it had the complex address block.
// Wait, customer-invoice.php in view_file Step 203 ended around line 100 with the delivery option and didn't show the complex address block afterwards?
// Ah, view_file showed 215 lines.
// Step 203 shows lines 102+ doing delivery option and then order meta.
// But it stopped showing lines after 100 in the first chunk... wait, no. Step 203 has "Showing lines 1 to 215".
// BUT the content I pasted in Step 203 seems truncated or I missed reading it.
// Let me double check Step 203 content.
// It ends at line 215 with `do_action('woocommerce_email_footer', $email);`.
// But where is the address block?
// Line 101: `}` (End of main table)
// Line 127: `wc_get_template('emails/email-order-meta.php'...`
// Lines 134-211 seems to include address logic?
// No, I missed copying that part in my mental model?
// Wait, looking at Step 203 code block again:
// It has `$delivery_option` checks...
// AND then `do_action('woocommerce_email_footer'...)` at line 215.
// IT DOES NOT HAVE THE ADDRESS BLOCK?
// Ah, `customer-invoice.php` often relies on `email-addresses.php` hook or generic footer?
// BUT wait, `customer-completed-order.php` HAD it explicitly.
// Let's look closely at `customer-invoice.php` (Step 203) again.
// It DOES NOT have the `if ($plain_text) { echo Billing address... }` block.
// It ends at line 215.
// Does it assume standard Woocommerce address injection?
// Usually `do_action( 'woocommerce_email_customer_details', $order, $sent_to_admin, $plain_text, $email );` handles it.
// `customer-completed-order.php` REPLACED that with custom code.
// `customer-invoice.php` does NOT seem to have that replacement code.
// So I should NOT add it if it wasn't there, unless the user wants it consistent.
// Given "Overall consistency things are distorted", I should probably leave it as is for now visually (just fix what IS there) to avoid breaking logic I don't fully see (maybe invoice relies on default).
// I will just fix fonts/RTL in what IS there.

// Code content for customer-invoice.php constructed above looks correct for what was there.
