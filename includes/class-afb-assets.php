<?php
namespace AFB; defined('ABSPATH') || exit;
class Assets {
  public static function init() : void { add_action('wp_enqueue_scripts',[__CLASS__,'enqueue']); }
  public static function enqueue() : void {
    if(!class_exists('\\WooCommerce')) return;
    wp_enqueue_style('afb-offcanvas', AFB_OFFCANVAS_URL.'assets/css/style.css',[],AFB_OFFCANVAS_VERSION);
    wp_enqueue_style('afb-auth', AFB_OFFCANVAS_URL.'assets/css/auth.css',['afb-offcanvas'],AFB_OFFCANVAS_VERSION);
    wp_enqueue_script('afb-offcanvas', AFB_OFFCANVAS_URL.'assets/js/script.js',['jquery'],AFB_OFFCANVAS_VERSION,true);
    wp_localize_script('afb-offcanvas','AFB_AJAX',[ 'url'=>admin_url('admin-ajax.php'),'nonce'=>wp_create_nonce('afb_nonce'),
      'i18n'=>['empty'=>__('Votre panier est vide.','afb-offcanvas')] ]);
  }
}