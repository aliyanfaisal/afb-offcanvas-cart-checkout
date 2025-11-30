<?php
namespace AFB; defined('ABSPATH') || exit;
class Cart {
  public static function init() : void {
    add_action('wp_ajax_afb_cart_open',[__CLASS__,'ajax_cart_html']);
    add_action('wp_ajax_nopriv_afb_cart_open',[__CLASS__,'ajax_cart_html']);
    add_action('wp_ajax_afb_cart_update_qty',[__CLASS__,'ajax_update_qty']);
    add_action('wp_ajax_nopriv_afb_cart_update_qty',[__CLASS__,'ajax_update_qty']);
    add_action('wp_ajax_afb_cart_remove',[__CLASS__,'ajax_remove_item']);
    add_action('wp_ajax_nopriv_afb_cart_remove',[__CLASS__,'ajax_remove_item']);
    add_action('wp_ajax_afb_add_to_cart',[__CLASS__,'ajax_add_item']);
    add_action('wp_ajax_nopriv_afb_add_to_cart',[__CLASS__,'ajax_add_item']);
  }
  protected static function render_items_html() : string {
    ob_start();
    if ( WC()->cart->is_empty() ) {
      echo '<div class="afb-cart-empty">'.esc_html__('Votre panier est vide.','afb-offcanvas').'</div>';
    } else {
      foreach ( WC()->cart->get_cart() as $cart_item_key=>$cart_item ) {
        $product = $cart_item['data']; if(!$product||!$product->exists()) continue;
        $thumb = $product->get_image('woocommerce_thumbnail'); $name=$product->get_name();
        $price = WC()->cart->get_product_price($product); $qty = $cart_item['quantity']; ?>
        <div class="afb-cart-row" data-cart-key="<?php echo esc_attr($cart_item_key); ?>">
          <div class="afb-cart-thumb"><?php echo $thumb; ?></div>
          <div class="afb-cart-details">
            <div class="afb-cart-name"><?php echo esc_html($name); ?></div>
            <div class="afb-cart-qty">
              <button class="afb-qty-btn" data-delta="-1" aria-label="<?php esc_attr_e('Diminuer','afb-offcanvas'); ?>">−</button>
              <span class="afb-qty-val"><?php echo esc_html($qty); ?></span>
              <button class="afb-qty-btn" data-delta="1" aria-label="<?php esc_attr_e('Augmenter','afb-offcanvas'); ?>">+</button>
            </div>
          </div>
          <div class="afb-cart-price"><?php echo $price; ?></div>
          <button class="afb-cart-remove" aria-label="<?php esc_attr_e('Supprimer','afb-offcanvas'); ?>">×</button>
        </div>
      <?php }
    }
    return ob_get_clean();
  }
  protected static function render_totals_html() : string {
    ob_start(); ?>
    <div class="afb-cart-total"><span><?php esc_html_e('Total incl. VAT','afb-offcanvas'); ?></span>
    <span><?php wc_cart_totals_order_total_html(); ?></span></div>
    <?php return ob_get_clean();
  }
  public static function ajax_cart_html() : void {
    check_ajax_referer('afb_nonce','nonce'); if(!class_exists('\\WooCommerce')) wp_send_json_error(['message'=>'Woo inactive']);
    WC()->cart->calculate_totals();
    wp_send_json_success(['items'=>self::render_items_html(),'totals'=>self::render_totals_html()]);
  }
  public static function ajax_update_qty() : void {
    check_ajax_referer('afb_nonce','nonce'); $key=sanitize_text_field($_POST['cart_key']??''); $delta=intval($_POST['delta']??0);
    if(!$key||0===$delta) wp_send_json_error();
    $cart=WC()->cart; if(!isset($cart->cart_contents[$key])) wp_send_json_error();
    $new_qty=max(0,intval($cart->cart_contents[$key]['quantity'])+$delta);
    if($new_qty<=0){ $cart->remove_cart_item($key);} else { $cart->set_quantity($key,$new_qty,true); }
    $cart->calculate_totals();
    wp_send_json_success(['items'=>self::render_items_html(),'totals'=>self::render_totals_html()]);
  }
  public static function ajax_remove_item() : void {
    check_ajax_referer('afb_nonce','nonce'); $key=sanitize_text_field($_POST['cart_key']??''); if(!$key) wp_send_json_error();
    WC()->cart->remove_cart_item($key); WC()->cart->calculate_totals();
    wp_send_json_success(['items'=>self::render_items_html(),'totals'=>self::render_totals_html()]);
  }
  public static function ajax_add_item() : void {
    check_ajax_referer('afb_nonce','nonce');
    $pid = intval($_POST['product_id']??0);
    $vid = intval($_POST['variation_id']??0);
    $qty = max(1,intval($_POST['qty']??1));
    if(!$pid){ wp_send_json_error(['message'=>'bad_request']); }
    $cart = WC()->cart; if(!$cart){ wp_send_json_error(['message'=>'no_cart']); }
    $variation = [];
    $added = $cart->add_to_cart($pid,$qty,$vid,$variation);
    if($added){
      $cart->calculate_totals();
      wp_send_json_success(['items'=>self::render_items_html(),'totals'=>self::render_totals_html()]);
    } else {
      wp_send_json_error(['message'=>'add_failed']);
    }
  }
}