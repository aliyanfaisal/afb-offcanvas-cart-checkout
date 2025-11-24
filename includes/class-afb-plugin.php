<?php
namespace AFB;

defined('ABSPATH') || exit;

// namespace AFB; defined('ABSPATH') || exit;
// class Plugin {
//   public static function init() : void {
//     if(!class_exists('\\WooCommerce')){ add_action('admin_notices', function(){ echo '<div class="notice notice-error"><p>'.esc_html__('AFB Off-Canvas Cart requires WooCommerce.','afb-offcanvas').'</p></div>';}); return; }
//     Assets::init(); Cart::init();
//     add_action('wp_footer',[__CLASS__,'render_cart'],9);
//     add_action('wp_body_open',[__CLASS__,'render_cart'],9);
//     add_shortcode('afb_cart_panel', function(){ ob_start(); self::render_cart(); return ob_get_clean(); });
//   }
//   public static function render_cart() : void {
//     if(is_admin()) return; static $done=false; if($done) return; $done=true;
//     include AFB_OFFCANVAS_DIR.'templates/cart/panel-cart.php';
//     include AFB_OFFCANVAS_DIR.'templates/checkout/panel-checkout.php';
//   }
// }
// 
// 
// 
 


class Plugin {
    
    public static function init() : void {
        // Check if WooCommerce is active
        if (!class_exists('\\WooCommerce')) {
            add_action('admin_notices', function(){
                echo '<div class="notice notice-error"><p>'.esc_html__('AFB Off-Canvas Cart requires WooCommerce.','afb-offcanvas').'</p></div>';
            });
            return;
        }
        
        // Hook into WooCommerce initialization
        add_action('woocommerce_init', [__CLASS__, 'woocommerce_ready']);
        
        // For frontend rendering, use a later hook to ensure WC() is available
        add_action('woocommerce_init', [__CLASS__, 'frontend_init']);
    }
    
    /**
     * Called when WooCommerce is fully initialized
     */
    public static function woocommerce_ready() : void {
        // Initialize your classes that need WooCommerce
        Assets::init();
        Cart::init();
        
        // Register shortcode
        add_shortcode('afb_cart_panel', function(){
            ob_start();
            self::render_cart();
            return ob_get_clean();
        });
    }
    
    /**
     * Called on wp hook - ensures WC() is available for frontend
     */
    public static function frontend_init() : void {
        if (is_admin()) return;
        // Avoid rendering while Elementor editor is active
        $is_elementor = (
            isset($_GET['elementor']) || isset($_GET['elementor-preview']) ||
            (class_exists('Elementor\\Plugin') && isset(\Elementor\Plugin::$instance) &&
             method_exists(\Elementor\Plugin::$instance->editor, 'is_edit_mode') &&
             \Elementor\Plugin::$instance->editor->is_edit_mode())
        );
        if ($is_elementor) return;
        
        // Add the render hooks here so WC() is available when templates are rendered
        add_action('wp_footer', [__CLASS__, 'render_cart'], 9);
        add_action('wp_body_open', [__CLASS__, 'render_cart'], 9);
    }
    
    public static function render_cart() : void {
        if (is_admin()) return;
        // Also skip rendering during Elementor edit mode
        $is_elementor = (
            isset($_GET['elementor']) || isset($_GET['elementor-preview']) ||
            (class_exists('Elementor\\Plugin') && isset(\Elementor\Plugin::$instance) &&
             method_exists(\Elementor\Plugin::$instance->editor, 'is_edit_mode') &&
             \Elementor\Plugin::$instance->editor->is_edit_mode())
        );
        if ($is_elementor) return;
         
        static $done = false;
        if ($done) return;
        $done = true;
        
        // At this point, WC() should be available
        if (!function_exists('WC') || !WC()) {
            return; // Safety check
        }
        
        include AFB_OFFCANVAS_DIR.'templates/cart/panel-cart.php';
        include AFB_OFFCANVAS_DIR.'templates/checkout/panel-checkout.php';
		
		
// 		add_filter('woocommerce_is_checkout', '__return_true');
		 
    }
}