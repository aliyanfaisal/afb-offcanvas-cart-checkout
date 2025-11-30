<?php
defined('ABSPATH') || exit;
$order = isset($order) && is_a($order, 'WC_Order') ? $order : null;
if (!$order) {
  $order_id = absint(get_query_var('order-received'));
  if ($order_id) {
    $order = wc_get_order($order_id);
  }
}
?>
<style>
  address{
    padding: 10px !important;
    margin-top: 10px !important;
  }
  .afb-thankyou {
    max-width: 980px;
    margin: 24px auto;
    padding: 24px;
    /* background: #f4ece5; */
    color: #232323;
    letter-spacing: .02em
  }

  .afb-thankyou-header {
    text-align: center;
    margin-bottom: 24px
  }

  .afb-thankyou-header .afb-check {
    font-size: 22px;
    color: #2e7d32
  }

  .afb-thankyou-header .afb-title {
    font-size: 18px;
    font-weight: 700;
    margin-top: 6px
  }

  .afb-thankyou-header .afb-sub {
    font-size: 13px;
    margin-top: 8px
  }

  .afb-order-header {
    display: flex;
    justify-content: space-between;
    border-top: 1px solid #d4c9bf;
    border-bottom: 1px solid #d4c9bf;
    padding: 10px 0;
    margin-top: 16px
  }

  .afb-order-ref {
    font-size: 12px;
    font-weight: 600
  }

  .afb-order-title {
    font-size: 12px;
    font-weight: 600
  }

  .afb-order-items {
    list-style: none;
    margin: 0;
    padding: 0
  }

  .afb-order-item {
    display: flex;
    align-items: center;
    border-bottom: 1px solid #e0d7cf;
    padding: 14px 0
  }

  .afb-order-item-image {
    width: 96px;
    min-width: 96px;
    margin-right: 16px
  }

  .afb-order-item-image img {
    width: 96px;
    height: auto;
    border-radius: 4px
  }

  .afb-order-item-main {
    flex: 1;
    min-width: 0
  }

  .afb-order-item-name {
    font-size: 14px;
    font-weight: 600;
    margin-bottom: 6px
  }

  .afb-order-item-meta {
    font-size: 12px;
    color: #666
  }

  .afb-order-item-cols {
    display: flex;
    gap: 24px;
    font-size: 12px;
    flex-wrap: wrap
  }

  .afb-col {
    min-width: 120px
  }

  .afb-summary {
    border-top: 1px solid #d4c9bf;
    padding-top: 12px;
    margin-top: 8px
  }

  .afb-summary-row {
    display: flex;
    justify-content: space-between;
    font-size: 12px;
    padding: 6px 0
  }

  .afb-summary-row.afb-total {
    font-weight: 700
  }

  .afb-bottom {
    display: flex;
    justify-content: space-between;
    border-top: 1px solid #d4c9bf;
    padding-top: 10px;
    margin-top: 12px;
    font-size: 12px
  }

  .afb-order-item-cols {
    column-gap: 24px
  }

  .afb-order-item-cols>.afb-col {
    margin-inline-end: 24px
  }

  .afb-order-item-cols>.afb-col:last-child {
    margin-inline-end: 0
  }

  .afb-rtl {
    direction: rtl
  }

  .afb-rtl .afb-order-header {
    flex-direction: row-reverse
  }

  .afb-rtl .afb-bottom {
    flex-direction: row-reverse
  }

  @media (max-width: 768px) {
    .afb-thankyou { padding: 16px }
    .afb-order-item-image { width: 80px; min-width: 80px }
    .afb-order-item-image img { width: 80px }
    .afb-order-item-cols { gap: 12px }
  }

  @media (max-width: 480px) {
    .afb-order-header, .afb-bottom { flex-direction: column; align-items: flex-start; gap: 6px }
    .afb-order-item { flex-direction: column; align-items: flex-start }
    .afb-order-item-main { padding-left: 0 !important; padding-right: 0 !important }
    .afb-order-item-cols { display: grid; grid-template-columns: 1fr; column-gap: 0; row-gap: 8px }
    .afb-col { min-width: 0 }
    .afb-order-item-name, .afb-order-item-meta { word-break: break-word; overflow-wrap: anywhere }
  }
</style>
<div class="afb-thankyou<?php echo is_rtl() ? ' afb-rtl' : ''; ?>">
  <div class="afb-thankyou-header">
    <div class="afb-check">✓</div>
    <div class="afb-title"><?php echo esc_html__("Votre commande est confirmée", "afb-offcanvas"); ?></div>
    <?php if ($order): ?>
      <div class="afb-sub">
        <?php echo wp_kses_post(sprintf(esc_html__("Un e‑mail vous a été envoyé à l’adresse %s.", "afb-offcanvas"), esc_html($order->get_billing_email()))); ?>
        <br>
        <?php echo wp_kses_post(sprintf(esc_html__("La référence de commande est %s.", "afb-offcanvas"), esc_html($order->get_order_number()))); ?>
      </div>
    <?php endif; ?>
  </div>

  <?php if ($order): ?>
    <?php
    $delivery_option = (string) $order->get_meta('afb_delivery_option');
    if ($delivery_option === '') {
      $delivery_option = (string) get_post_meta($order->get_id(), 'afb_delivery_option', true);
    }
    $delivery_label = '';
    if ($delivery_option === 'pickup') {
      $delivery_label = esc_html__('Retrait magasin', 'afb-offcanvas');
    } elseif ($delivery_option === 'multiship') {
      $delivery_label = esc_html__('Livraison multiple', 'afb-offcanvas');
    } else {
      $delivery_label = $order->get_shipping_method();
      if ($delivery_label === '') {
        $delivery_label = '—';
      }
    }
    ?>
    <div class="afb-order-header">
      <div class="afb-order-ref"><?php echo esc_html__("Référence de commande :", "afb-offcanvas") . " " . esc_html($order->get_order_number()); ?></div>
      <div class="afb-order-title"><?php echo esc_html__("Articles de la commande", "afb-offcanvas"); ?></div>
    </div>

    <ul class="afb-order-items">
      <?php foreach ($order->get_items() as $item_id => $item): ?>
        <?php $_product = $item->get_product();
        if (!$_product) continue; ?>
        <li class="afb-order-item">
          <div class="afb-order-item-image"  >
            <?php echo $_product->get_image('woocommerce_thumbnail'); ?>
          </div>
          <div class="afb-order-item-main">
            <div class="afb-order-item-name"><?php echo esc_html($_product->get_name()); ?></div>
            <div class="afb-order-item-meta"><?php wc_display_item_meta($item, array('echo' => true)); do_action('woocommerce_order_item_meta_end', $item_id, $item, $order, false); ?></div>
            <div class="afb-order-item-cols">
              <div class="afb-col">
                <div><?php echo esc_html__("Prix unitaire:", "afb-offcanvas"); ?></div>
                <div><?php $unit = ($item->get_total() + $item->get_total_tax()) / max(1, $item->get_quantity());
                      echo wc_price($unit); ?></div>
              </div>
              <div class="afb-col">
                <div><?php echo esc_html__("Quantité:", "afb-offcanvas"); ?></div>
                <div><?php echo esc_html($item->get_quantity()); ?></div>
              </div>
              <div class="afb-col">
                <div><?php echo esc_html__("Total produits:", "afb-offcanvas"); ?></div>
                <div><?php echo wc_price($item->get_total() + $item->get_total_tax()); ?></div>
              </div>
            </div>
          </div>
        </li>
      <?php endforeach; ?>
    </ul>

    <div class="afb-summary">
      <div class="afb-summary-row">
        <div><?php echo esc_html__("Sous‑total", "afb-offcanvas"); ?></div>
        <div><?php echo wc_price($order->get_subtotal()); ?></div>
      </div>
      <div class="afb-summary-row">
        <div><?php echo esc_html__("Frais de livraison", "afb-offcanvas"); ?></div>
        <div><?php echo $order->get_shipping_total() > 0 ? wc_price($order->get_shipping_total()) : esc_html__("Gratuit", "afb-offcanvas"); ?></div>
      </div>
      <div class="afb-summary-row afb-total">
        <div><?php echo esc_html__("Total TTC", "afb-offcanvas"); ?></div>
        <div><?php echo wc_price($order->get_total()); ?></div>
      </div>
    </div>

    <div class="afb-bottom">
      <div><?php echo esc_html__("Référence de la commande :", "afb-offcanvas") . " " . esc_html($order->get_order_number()); ?></div>
      <div><?php echo esc_html__("Moyen de paiement :", "afb-offcanvas") . " " . esc_html($order->get_payment_method_title()); ?></div>
      <div><?php echo esc_html__("Mode de livraison :", "afb-offcanvas") . " " . esc_html($delivery_label); ?></div>
    </div>
  <?php endif; ?>


  <div style="margin-top: 30px;">

  <?php if ($order) {

    wc_get_template('order/order-details-customer.php', array('order' => $order));

    // Apply the same layout adjustments as plugin hook for pickup/multiship
    $flag = (bool) $order->get_meta('afb_is_multiship');
    if (!$flag) { $flag = (bool) get_post_meta($order->get_id(), 'afb_is_multiship', true); }
    $is_multiship = $flag || ($delivery_option === 'multiship');
    $is_pickup    = ($delivery_option === 'pickup');
    if ($is_multiship || $is_pickup) {
      echo '<style>
        .woocommerce-columns--addresses .woocommerce-column--shipping-address { display: none !important; }
        table.shop_table.order_details .order-actions, .order-actions, .woocommerce-orders-table__cell-order-actions { display: none !important; }
        table.shop_table.order_details tfoot .shipping { display: none !important; }
        .woocommerce-order-overview__shipping { display: none !important; }'
        . ($is_pickup ? ' .woocommerce-customer-details > address { display: none !important; }' : '') .
      '</style>';
    }
   


  } ?>

  </div>
</div>
