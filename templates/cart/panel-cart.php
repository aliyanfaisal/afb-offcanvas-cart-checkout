<?php defined('ABSPATH') || exit; ?>
<div id="afb-cart-panel" class="afb-panel afb-cart-panel" aria-hidden="true" role="dialog" aria-label="<?php esc_attr_e('Panier','afb-offcanvas'); ?>">
  <div class="afb-panel__overlay" data-afb-close></div>
  <aside class="afb-panel__sheet" dir="<?php echo is_rtl() ? 'rtl' : 'ltr'; ?>">
    <header class="afb-panel__header">
      <div class="afb-panel__title"><?php esc_html_e('PANIER','afb-offcanvas'); ?></div>
      <button class="afb-panel__close" type="button" data-afb-close><?php esc_html_e('FERMER','afb-offcanvas'); ?></button>
    </header>
    <div class="afb-divider"></div>
    <div class="afb-panel__body">
      <div class="afb-cart-items" data-afb-cart-items>
        <?php
        // Server-side render cart items using exact same logic as Cart class
        if (class_exists('WooCommerce') && function_exists('WC') && WC()->cart) {
          WC()->cart->calculate_totals();
          
          // Use exact same logic as AFB\Cart::render_items_html()
          if (WC()->cart->is_empty()) {
            echo '<div class="afb-cart-empty">'.esc_html__('Votre panier est vide.','afb-offcanvas').'</div>';
          } else {
            foreach (WC()->cart->get_cart() as $cart_item_key=>$cart_item) {
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
        }
        ?>
      </div>
    </div>
<!--     <div class="afb-divider"></div> -->
    <footer class="afb-panel__footer">
      <div class="afb-cart-summary" data-afb-cart-summary>
        <?php
        // Server-side render cart totals using exact same logic as Cart class
        if (class_exists('WooCommerce') && function_exists('WC') && WC()->cart) {
          // Use exact same logic as AFB\Cart::render_totals_html()
          ?>
          <div class="afb-cart-total"><span><?php esc_html_e('Total incl. VAT','afb-offcanvas'); ?></span>
          <span><?php wc_cart_totals_order_total_html(); ?></span></div>
          <?php
        }
        ?>
      </div>
      <div class="afb-actions">
        <button class="afb-btn afb-btn--ghost" data-afb-close><?php esc_html_e('CONTINUER MES ACHATS','afb-offcanvas'); ?></button>
        <a class="afb-btn afb-btn--primary" href="<?php echo esc_url( wc_get_checkout_url() ); ?>"><?php esc_html_e('PASSER COMMANDE','afb-offcanvas'); ?></a>
      </div>
    </footer>
  </aside>
	
	
	<style>
		
		
/* 		//RTL */
		.rtl #afb-cart-panel  .afb-cart-remove{
			margin-right: -75px !important;
		}
		.rtl #afb-cart-panel .afb-panel__sheet{
			left: 0px !important;
    		right: auto;
		}
		
		.rtl #afb-checkout-panel .afb-panel__sheet{
			left: auto !important;
    		right: 0px;
		}
		
		.rtl .afb-curr-user-log{
			text-align: right !important
		}
		
		
		
		.afb-curr-user-log{
			text-align: left !important;
		}
		
		.afb-cart-items .afb-cart-remove{
			padding: 0px !important;
			align-self: flex-end !important;
			
			font-weight: 300 !important;
			font-size: 24px !important;
		}
		
		.afb-cart-items .afb-cart-remove:not(.rtl .afb-cart-items .afb-cart-remove ){
			margin-left: -75px !important;
		}
		
		
		.afb-cart-remove:hover{
			color: #7C4E3E !important;
			background: none !important
		}
		
		.afb-cart-qty{
			margin-left:0px !important;
			gap: 8px !important
		}
		
		.afb-cart-qty .afb-qty-btn{
			padding: 0px !important
		}
		
		.afb-cart-qty .afb-qty-btn:hover{
			color:#1b1b1b !important
		}
		
		.afb-qty-val{
			font-size:15px !important;
			font-weight: 600 !important
		}
		
		.afb-cart-row{
			align-items: stretch !important;
		}
		
		.afb-cart-details{
			display: flex !important;
			justify-content: space-between !important;
			flex-direction: column !important;
		}
		
		.afb-cart-thumb img {
			width: 118px !important;
			height: 152px !important
		}
		
		.afb-cart-name,
		.afb-cart-price span{
			color: #1d1d1b !important;
    		font-weight: 500 !important;
		}
		
	 
		
		.afb-cart-total{
			display: flex;
			justify-content: space-between;
			flex: 1;
		}
		.afb-cart-total span{
			font-weight: 400 !important
		}
		
		.afb-actions > .afb-btn{
			flex: 1 ;
			padding: 16px !important
		}
		.afb_panier_opener .elementor-menu-cart__container{
			display: none !important
		}
		
		
		.afb-cart-remove:focus{
			color: black !important;
			background: transparent !important
		}
		@media (max-width: 768px){
			
			#afb-cart-panel{
				margin-top:60px
			}
			.afb-cart-thumb img {
				width: 66px !important;
				height: 85px !important
			}
			
			.afb-cart-remove{
				font-size: 20px !important
			}
		}
	</style>
</div>
