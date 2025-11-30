<?php

// Add reorder button to my orders
add_filter('woocommerce_my_account_my_orders_actions', 'add_reorder_button_to_orders', 10, 2);
function add_reorder_button_to_orders($actions, $order) {
    // Only show for completed and processing orders
    if (in_array($order->get_status(), array('completed', 'processing'))) {
        $actions['reorder'] = array(
            'url'  => wp_nonce_url(
                add_query_arg(array(
                    'reorder' => $order->get_id()
                ), wc_get_account_endpoint_url('orders')),
                'woocommerce-reorder',
                'reorder_nonce'
            ),
            'name' => __('Reorder', 'woocommerce'),
            'class' => 'button reorder'
        );
    }
    return $actions;
}

// Handle the reorder request
add_action('wp_ajax_afb_get_order_items', 'afb_get_order_items');
function afb_get_order_items() {
    if (!isset($_POST['order_id']) || !isset($_POST['reorder_nonce'])) { wp_send_json_error(array('error' => 'bad_request')); }
    if (!wp_verify_nonce($_POST['reorder_nonce'], 'woocommerce-reorder')) { wp_send_json_error(array('error' => 'invalid_nonce')); }
    $order_id = absint($_POST['order_id']);
    if (!current_user_can('view_order', $order_id)) { wp_send_json_error(array('error' => 'forbidden')); }
    $order = wc_get_order($order_id);
    if (!$order) { wp_send_json_error(array('error' => 'order_not_found')); }
    $items = array();
    foreach ($order->get_items() as $item) {
        $items[] = array(
            'product_id'   => (int) $item->get_product_id(),
            'variation_id' => (int) $item->get_variation_id(),
            'qty'          => max(1, (int) $item->get_quantity()),
            'order_id'     => $order_id,
        );
    }
    $afb_nonce = wp_create_nonce('afb_nonce');
    wp_send_json_success(array('items' => $items, 'afb_nonce' => $afb_nonce));
}

// Add simple CSS for the button
add_action('wp_head', 'add_reorder_button_css');
function add_reorder_button_css() {
    if (is_account_page()) {
        ?>
        <style>
        .reorder {
            background-color: #0073aa !important;
            color: white !important;
            padding: 6px 12px !important;
            border-radius: 3px !important;
            text-decoration: none !important;
            font-size: 12px !important;
            display: inline-block !important;
        }
        .reorder:hover {
            background-color: #005a87 !important;
            color: white !important;
        }
        #afb-reorder-loader { position: fixed; inset: 0; display: none; align-items: center; justify-content: center; background: rgba(0,0,0,0.15); z-index: 999999; }
        #afb-reorder-loader.active { display: flex; }
        #afb-reorder-loader .inner { background: #fff; padding: 12px 16px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px; color: #333; }
        </style>
        <script>
        document.addEventListener('click', function(e){
            var t = e.target;
            if (t && t.classList && t.classList.contains('reorder')) {
                e.preventDefault();
                var loader = document.getElementById('afb-reorder-loader');
                if (!loader) { loader = document.createElement('div'); loader.id = 'afb-reorder-loader'; loader.innerHTML = '<div class="inner">Processing…</div>'; document.body.appendChild(loader); }
                loader.classList.add('active');
                try { t.disabled = true; t.classList.add('loading'); } catch (err) {}
                var href = t.getAttribute('href') || '';
                var m1 = href.match(/[?&]reorder=(\d+)/);
                var m2 = href.match(/[?&]reorder_nonce=([^&#]+)/);
                if (!m1 || !m2) { return; }
                var orderId = parseInt(m1[1], 10);
                var nonce = decodeURIComponent(m2[1]);
                var ajaxUrl = '<?php echo esc_js( admin_url('admin-ajax.php') ); ?>';
                fetch(ajaxUrl, { method:'POST', headers:{ 'Content-Type':'application/x-www-form-urlencoded' }, body: new URLSearchParams({ action:'afb_get_order_items', order_id: orderId, reorder_nonce: nonce }) })
                .then(function(r){ return r.json(); })
                .then(function(json){
                    if (!json || !json.success) { try { loader.classList.remove('active'); t.disabled=false; t.classList.remove('loading'); } catch (err) {} alert('Failed to load order items'); return; }
                    var items = json.data.items || [];
                    var afbNonce = json.data.afb_nonce || '';
                    var seq = Promise.resolve();
                    items.forEach(function(it){
                        seq = seq.then(function(){
                            return fetch(ajaxUrl, { method:'POST', headers:{ 'Content-Type':'application/x-www-form-urlencoded' }, body: new URLSearchParams({ action:'afb_add_to_cart', nonce: afbNonce, product_id: it.product_id, variation_id: it.variation_id, qty: it.qty }) })
                                   .then(function(r){ return r.json(); });
                        });
                    });
                    seq.then(function(){
                        try {
                            var url = new URL(window.location.href);
                            url.searchParams.set('open_afb_cart', 'true');
                            window.location.href = url.toString();
                        } catch (e) { window.location.reload(); }
                    });
                });
            }
        });
        </script>
        <?php
    }
}