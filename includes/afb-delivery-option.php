<?php

add_action('woocommerce_checkout_update_order_meta', 'save_delivery_option_to_order', 10, 2);

function save_delivery_option_to_order($order_id, $data) {
    if (isset($_POST['afb_delivery_option'])) {
        $delivery_option = sanitize_text_field($_POST['afb_delivery_option']);
        update_post_meta($order_id, 'afb_delivery_option', $delivery_option);
        $order = wc_get_order($order_id);
        if ($order && is_a($order, 'WC_Order')) {
            $order->update_meta_data('afb_delivery_option', $delivery_option);
            $order->save();
        }
    }
}

add_action('woocommerce_admin_order_data_after_shipping_address', 'display_delivery_option_in_admin', 10, 1);

function display_delivery_option_in_admin($order) {
    $delivery_option = get_post_meta($order->get_id(), 'afb_delivery_option', true);
    $pickup_location = get_post_meta($order->get_id(), '_pickup_location', true);
    
    if (!empty($delivery_option)) {
        // Map delivery options to display names
        $option_labels = [
            'pickup' => 'Retrait en magasin',
            'multiship' => 'Livraison multiple', 
            'standard' => 'Livraison standard'
        ];
        
        $display_name = isset($option_labels[$delivery_option]) ? $option_labels[$delivery_option] : $delivery_option;
        
        echo '<div class="order-delivery-option">';
        echo '<h3>Option de livraison</h3>'; 
        echo '<p>' . htmlspecialchars($display_name) . '</p>';
        if ($delivery_option === 'pickup' && !empty($pickup_location)) {
            echo '<div class="order-pickup-location" style="margin-top:8px">';
            echo '<h4 style="margin:0 0 6px;">' . esc_html__('In-Store Pickup Info', 'afb-offcanvas') . '</h4>';
            $info = function_exists('afb_get_store_info') ? afb_get_store_info($pickup_location) : null;
            if ($info && ($info['address'] || $info['city'] || $info['phone'])) {
                echo '<p style="margin:0;">' . esc_html( $info['name'] ) . '</p>';
                if ($info['address']) { echo '<p style="margin:0;">' . esc_html( $info['address'] ) . '</p>'; }
                if ($info['city'])    { echo '<p style="margin:0;">' . esc_html( $info['city'] ) . '</p>'; }
                if ($info['phone'])   { echo '<p style="margin:0;">' . esc_html( $info['phone'] ) . '</p>'; }
            } else {
                echo '<p style="margin:0;">' . esc_html( function_exists('afb_format_pickup_location') ? afb_format_pickup_location($pickup_location) : $pickup_location ) . '</p>';
            }
            echo '</div>';
        }
        echo '</div>';
        
        // Add some basic styling
        echo '<style>
            .order-delivery-option {
                margin-top: 20px;
                padding: 15px;
                background: #f5f5f5;
                border: 1px solid #ddd;
            }
        </style>';
    }
}