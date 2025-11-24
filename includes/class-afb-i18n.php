<?php
namespace AFB;
defined('ABSPATH') || exit;
class I18n { public static function load_textdomain() : void {
    load_plugin_textdomain('afb-offcanvas', false, dirname(plugin_basename(AFB_OFFCANVAS_DIR . 'afb-offcanvas-cart.php')) . '/languages');
}}