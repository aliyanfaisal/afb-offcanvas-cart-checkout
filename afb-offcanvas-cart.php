<?php

/**
 * Plugin Name: AFB Off-Canvas Cart (Slide-In) - Final
 * Description: Pixel-perfect off-canvas Cart drawer for WooCommerce. Store info in emails and typ. zooming . Store name into ID - EMAIL CHANGES - added custom order item section -- added textdomain to json res -- reorder button
 * Version: 0.4.4
 * Author: AFB
 * Text Domain: afb-offcanvas
 * Domain Path: /languages
 */
if (! defined('ABSPATH')) {
    exit;
}

// Check if we're in Elementor editor and exit if true
if (
    !isset($_GET['elementor-preview']) &&
    !(defined('ELEMENTOR_VERSION') &&
        is_admin() &&
        isset($_GET['action']) &&
        $_GET['action'] == 'elementor')
) {



    define('AFB_OFFCANVAS_VERSION', '0.2.0');
    define('AFB_OFFCANVAS_DIR', plugin_dir_path(__FILE__));
    define('AFB_OFFCANVAS_URL', plugin_dir_url(__FILE__));
    require_once AFB_OFFCANVAS_DIR . 'includes/class-afb-i18n.php';
    require_once AFB_OFFCANVAS_DIR . 'includes/afb-wc-ajax.php';

    require_once AFB_OFFCANVAS_DIR . 'includes/afb-pickup-store-field.php';
    require_once AFB_OFFCANVAS_DIR . 'includes/class-afb-assets.php';
    require_once AFB_OFFCANVAS_DIR . 'includes/class-afb-cart.php';
    require_once AFB_OFFCANVAS_DIR . 'includes/class-afb-plugin.php';
    require_once AFB_OFFCANVAS_DIR . 'includes/afb-order-message.php';
    require_once AFB_OFFCANVAS_DIR . 'includes/afb-multiship-phone.php';
    require_once AFB_OFFCANVAS_DIR . 'includes/afb-multiship-address.php';
    require_once AFB_OFFCANVAS_DIR . 'includes/afb-order-itemmeta-display.php';
    require_once AFB_OFFCANVAS_DIR . 'includes/afb-order-itemmeta-frontend.php';
    require_once AFB_OFFCANVAS_DIR . 'includes/afb-order-summary-meta.php';
    require_once AFB_OFFCANVAS_DIR . 'includes/afb-store-user-handler.php';
    require_once AFB_OFFCANVAS_DIR . 'includes/afb-split-cart-handler.php';
    require_once AFB_OFFCANVAS_DIR . 'includes/afb-delivery-field.php';
    require_once AFB_OFFCANVAS_DIR . 'includes/afb-delivery-option.php';
    require_once AFB_OFFCANVAS_DIR . 'includes/afb-order-pay-handler.php';
    require_once AFB_OFFCANVAS_DIR . 'includes/afb-email-filters.php';
    require_once AFB_OFFCANVAS_DIR . 'includes/afb-shipping-phone.php';
    require_once AFB_OFFCANVAS_DIR . 'includes/afb-atc-dom-observer.php';
    require_once AFB_OFFCANVAS_DIR . 'includes/afb-pickup-itemmeta-admin.php';
    require_once AFB_OFFCANVAS_DIR . 'includes/afb-pickup-itemmeta-frontend.php';
    require_once AFB_OFFCANVAS_DIR . 'includes/afb-email-templates.php';
    require_once AFB_OFFCANVAS_DIR . 'includes/afb-reorder-button.php';



    // Register order-pay hooks
    add_action('wp', 'afb_before_order_pay_display');
    add_filter('woocommerce_get_order_item_totals', 'afb_filter_order_pay_item_totals', 10, 3);
    add_action('woocommerce_checkout_order_processed', 'afb_save_multiship_data_to_order');
    add_filter('woocommerce_order_get_total', 'afb_modify_order_total_for_pay', 10, 2);



    add_action('plugins_loaded', function () {
        AFB\I18n::load_textdomain();
        AFB\Plugin::init();
    });




    add_action('wp_enqueue_scripts', function () {
        if (is_checkout()) {
            // These hooks help plugins detect  n checkout pagee
            do_action('woocommerce_checkout_init');
            do_action('woocommerce_checkout_process');


            if (function_exists('wc_checkout_params')) {
                wp_enqueue_script('wc-checkout');
            }
        }
    });

    add_action("wp_footer", function () { ?>
        <style>
            #afb_panier_opener,
            .afb_panier_opener {
                cursor: pointer
            }
        </style>
        <script>
            jQuery(function($) {
                $("#afb_panier_opener,.afb_panier_opener").on("click", (e) => {
                    e.preventDefault()

                    if (window.AFB && AFB.Cart) {
                        AFB.Cart.open();
                    }
                });
            });


            document.addEventListener('DOMContentLoaded', function() {
                // Check if open_afb_cart parameter is in URL
                const urlParams = new URLSearchParams(window.location.search);
                const openAfbCart = urlParams.get('open_afb_cart');

                if (openAfbCart === 'true') {
                    // Check if afb_cart_render function exists before calling
                    if (typeof AFB.Cart.open === 'function') {
                        AFB.Cart.open();
                    }
                }
            });
        </script>
    <?php });










    // add user data to frontend
    add_action('wp_head', function () {
        if (!is_user_logged_in()) {
            return;
        }

        $user_id = get_current_user_id();
        $user    = get_userdata($user_id);

        if (!$user) {
            return;
        }

        // User profile fields
        $user_fields = [
            'ID'           => $user->ID,
            'user_login'   => $user->user_login,
            'user_email'   => $user->user_email,
            'display_name' => $user->display_name,
            'roles'        => $user->roles,
            'first_name'   => $user->first_name,
            'last_name'       => $user->last_name,
        ];

        // WooCommerce billing fields
        $billing_fields = [
            'billing_first_name',
            'billing_last_name',
            'billing_company',
            'billing_address_1',
            'billing_address_2',
            'billing_city',
            'billing_postcode',
            'billing_country',
            'billing_state',
            'billing_phone',
            'billing_email'
        ];

        $user_data = $user_fields;

        foreach ($billing_fields as $field) {
            $user_data[$field] = get_user_meta($user_id, $field, true);
        }
    ?>
        <script>
            const afbUserData = <?php echo wp_json_encode($user_data); ?>;
        </script>
    <?php
    });










    // / login and reg handlerr 
    function handle_custom_user_registration()
    {
        if (!wp_verify_nonce($_POST['nonce'], 'create_user_nonce')) {
            wp_die( esc_html__( 'Security check failed', 'afb-offcanvas' ) );
        }

        $user_login = sanitize_email($_POST['user_email']);
        $user_email = sanitize_email($_POST['user_email']);
        $user_pass = $_POST['user_pass'];
        $first_name = sanitize_text_field($_POST['first_name']);
        $last_name = sanitize_text_field($_POST['last_name']);

        // Build user_login from first+last name; fallback to email local part if empty
        $name_combo = trim($first_name . ' ' . $last_name);
        if ($name_combo !== '') {
            $candidate = function_exists('remove_accents') ? remove_accents($name_combo) : $name_combo;
            $candidate = sanitize_title($candidate);
            $candidate = str_replace('-', '_', $candidate);
            $user_login = sanitize_user($candidate, true);
        } else {
            $parts = explode('@', $user_email);
            $local = isset($parts[0]) ? $parts[0] : $user_email;
            $user_login = sanitize_user($local, true);
        }

        $base_login = $user_login !== '' ? $user_login : 'user';
        if (username_exists($base_login)) {
            $tries = 0;
            do {
                $suffix = (string) wp_rand(100, 999);
                $user_login = sanitize_user($base_login . $suffix, true);
                $tries++;
            } while ($tries < 20 && username_exists($user_login));
        }

        $user_address = sanitize_text_field($_POST['user_address']);
        $user_city = sanitize_text_field($_POST['user_city']);
        $user_postal = sanitize_text_field($_POST['user_postal']);
        $user_country = sanitize_text_field($_POST['user_country']);
        $user_phone = sanitize_text_field($_POST['user_phone']) ??  sanitize_text_field($_POST['shipping_phone']);

        if (empty($user_login) || empty($user_email) || empty($user_pass)) {
            wp_send_json_error( esc_html__( 'Tous les champs requis doivent être remplis', 'afb-offcanvas' ) );
        }

        if (!is_email($user_email)) {
            wp_send_json_error( esc_html__( 'Adresse email invalide', 'afb-offcanvas' ) );
        }

        if (email_exists($user_email)) {
            wp_send_json_error( esc_html__( 'Un utilisateur avec cette adresse email existe déjà', 'afb-offcanvas' ) );
        }

        $user_id = wp_create_user($user_login, $user_pass, $user_email);

        if (is_wp_error($user_id)) {
            wp_send_json_error( esc_html( $user_id->get_error_message() ) );
        }

        wp_update_user(array(
            'ID' => $user_id,
            'first_name' => $first_name,
            'last_name' => $last_name,
            'display_name' => $first_name . ' ' . $last_name
        ));

        update_user_meta($user_id, 'user_address', $user_address);
        update_user_meta($user_id, 'user_city', $user_city);
        update_user_meta($user_id, 'user_postal', $user_postal);
        update_user_meta($user_id, 'user_country', $user_country);
        update_user_meta($user_id, 'user_phone', $user_phone);

        // Save billing information
        update_user_meta($user_id, 'billing_address_1', $user_address);
        update_user_meta($user_id, 'billing_city', $user_city);
        update_user_meta($user_id, 'billing_postcode', $user_postal);
        update_user_meta($user_id, 'billing_country', $user_country);
        update_user_meta($user_id, 'billing_phone', $user_phone);

        // Save WooCommerce shipping address fields
        update_user_meta($user_id, 'shipping_first_name', $first_name);
        update_user_meta($user_id, 'shipping_last_name', $last_name);
        update_user_meta($user_id, 'shipping_company', '');
        update_user_meta($user_id, 'shipping_address_1', $user_address);
        update_user_meta($user_id, 'shipping_address_2', '');
        update_user_meta($user_id, 'shipping_city', $user_city);
        update_user_meta($user_id, 'shipping_state', '');
        update_user_meta($user_id, 'shipping_postcode', $user_postal);
        update_user_meta($user_id, 'shipping_country', $user_country);
        update_user_meta($user_id, 'shipping_phone', $user_phone);

        // Create custom address data structure for thwma_custom_address
        //     $custom_address_data = array(
        //         'shipping' => array(
        //             'address_1' => array(
        //                 'shipping_heading' => $user_address,
        //                 'shipping_first_name' => $first_name,
        //                 'shipping_last_name' => $last_name,
        //                 'shipping_company' => '',  
        //                 'shipping_country' => $user_country,
        //                 'shipping_address_1' => $user_address,
        //                 'shipping_address_2' => '', 
        //                 'shipping_city' => $user_city,
        //                 'shipping_state' => '', 
        //                 'shipping_postcode' => $user_postal
        //             )
        //         ),
        //         'default_shipping' => 'address_1' // Set address_1 as default
        //     );
        // 	// Save the serialized custom address data
        //     update_user_meta($user_id, 'thwma_custom_address', $custom_address_data);


        $custom_address =  array(
            //                 'shipping_heading' => $user_address,
            'shipping_first_name' => $first_name,
            'shipping_last_name' => $last_name,
            'shipping_company' => '',
            'shipping_country' => $user_country,
            'shipping_address_1' => $user_address,
            'shipping_address_2' => '',
            'shipping_city' => $user_city,
            'shipping_state' => '',
            'shipping_postcode' => $user_postal
        );


        $custom_address = prepare_posted_address($user_id, $custom_address, 'shipping');


        if (class_exists('Themehigh\WoocommerceMultipleAddressesPro\includes\utils\THWMA_Utils')) {

            $savedd = Themehigh\WoocommerceMultipleAddressesPro\includes\utils\THWMA_Utils::save_address_to_user($user_id, $custom_address, 'shipping');
        }



        wp_set_current_user($user_id);
        wp_set_auth_cookie($user_id);


        wp_send_json_success( esc_html__( 'Utilisateur créé avec succès', 'afb-offcanvas' ) );
    }
    add_action('wp_ajax_custom_user_registration', 'handle_custom_user_registration');
    add_action('wp_ajax_nopriv_custom_user_registration', 'handle_custom_user_registration');


    function prepare_posted_address($user_id, $address, $type)
    {
        $address_new = array();
        if (!empty($address) && is_array($address)) {
            foreach ($address as $key => $value) {
                if (isset($address[$key])) {
                    $address_value = is_array($address[$key]) ? implode(', ', sanitize_key(wc_clean($address[$key]))) : wc_clean($address[$key]);
                }
                $address_new[$key] = $address_value;
            }
        }
        $default_heading = apply_filters('thwma_default_heading', false);
        if ($default_heading) {
            if ($type == 'billing') {
                if (isset($address_new['billing_heading']) && ($address_new['billing_heading'] == '')) {
                    $address_new['billing_heading'] = esc_html__('Home', 'woocommerce-multiple-addresses-pro');
                }
            } elseif ($type == 'shipping') {
                if (isset($address_new['shipping_heading']) && ($address_new['shipping_heading'] == '')) {
                    $address_new['shipping_heading'] = esc_html__('Home', 'woocommerce-multiple-addresses-pro');
                }
            }
        }
        return $address_new;
    }






    add_action('wp_ajax_nopriv_ajax_login', 'ajax_login_handler');
    function ajax_login_handler()
    {
        // Security check
        check_ajax_referer('ajax-login-nonce', 'security');

        // Build base redirect and translate to current WPML language
        $redirect_raw = !empty($_POST['redirect_to'])
            ? esc_url_raw($_POST['redirect_to'])
            : home_url();

        $current_lang = apply_filters('wpml_current_language', null);

        // Convert redirect to current language (no-op if WPML inactive)
        $redirect = !empty($_POST['redirect_to'])
            ? apply_filters('wpml_permalink', $redirect_raw, $current_lang)
            : apply_filters('wpml_home_url', $redirect_raw, $current_lang);

        $creds = [
            'user_login'    => sanitize_user($_POST['log']),
            'user_password' => $_POST['pwd'],
            'remember'      => !empty($_POST['rememberme']),
        ];

        $user = wp_signon($creds, false);

        if (is_wp_error($user)) {
            wp_send_json_error([
                'message' => $user->get_error_message()
            ]);
        } else {
            wp_send_json_success([
                'message'  => esc_html__( 'Login successful', 'afb-offcanvas' ),
                'redirect' => $redirect
            ]);
        }
    }





















    function add_custom_user_profile_fields($user)
    {
    ?>
        <h3><?php esc_html_e('Informations supplémentaires', 'afb-offcanvas'); ?></h3>
        <table class="form-table">
            <tr>
                <th><label for="user_address"><?php esc_html_e('Adresse', 'afb-offcanvas'); ?></label></th>
                <td>
                    <input type="text" name="user_address" id="user_address"
                        value="<?php echo esc_attr(get_user_meta($user->ID, 'user_address', true)); ?>"
                        class="regular-text" />
                </td>
            </tr>
            <tr>
                <th><label for="user_city"><?php esc_html_e('Ville', 'afb-offcanvas'); ?></label></th>
                <td>
                    <input type="text" name="user_city" id="user_city"
                        value="<?php echo esc_attr(get_user_meta($user->ID, 'user_city', true)); ?>"
                        class="regular-text" />
                </td>
            </tr>
            <tr>
                <th><label for="user_postal"><?php esc_html_e('Code postal', 'afb-offcanvas'); ?></label></th>
                <td>
                    <input type="text" name="user_postal" id="user_postal"
                        value="<?php echo esc_attr(get_user_meta($user->ID, 'user_postal', true)); ?>"
                        class="regular-text" />
                </td>
            </tr>
            <tr>
                <th><label for="user_country"><?php esc_html_e('Pays', 'afb-offcanvas'); ?></label></th>
                <td>
                    <select name="user_country" id="user_country">
                        <option value=""><?php esc_html_e('Choisir votre pays', 'afb-offcanvas'); ?></option>
                        <?php
                        $countries = array(
                            'FR' => __( 'France', 'afb-offcanvas' ),
                            'BE' => __( 'Belgique', 'afb-offcanvas' ),
                            'CH' => __( 'Suisse', 'afb-offcanvas' ),
                            'CA' => __( 'Canada', 'afb-offcanvas' ),
                            'US' => __( 'États-Unis', 'afb-offcanvas' ),
                            'DE' => __( 'Allemagne', 'afb-offcanvas' ),
                            'ES' => __( 'Espagne', 'afb-offcanvas' ),
                            'IT' => __( 'Italie', 'afb-offcanvas' ),
                            'UK' => __( 'Royaume-Uni', 'afb-offcanvas' )
                        );
                        $selected_country = get_user_meta($user->ID, 'user_country', true);
                        foreach ($countries as $code => $name) {
                            echo '<option value="' . esc_attr($code) . '"' . selected($selected_country, $code, false) . '>' . esc_html($name) . '</option>';
                        }
                        ?>
                    </select>
                </td>
            </tr>
            <tr>
                <th><label for="user_phone"><?php esc_html_e('Téléphone', 'afb-offcanvas'); ?></label></th>
                <td>
                    <input type="tel" name="user_phone" id="user_phone"
                        value="<?php echo esc_attr(get_user_meta($user->ID, 'user_phone', true)); ?>"
                        class="regular-text" />
                </td>
            </tr>
        </table>
    <?php
    }
    add_action('show_user_profile', 'add_custom_user_profile_fields');
    add_action('edit_user_profile', 'add_custom_user_profile_fields');

    function save_custom_user_profile_fields($user_id)
    {
        if (!current_user_can('edit_user', $user_id)) {
            return false;
        }

        update_user_meta($user_id, 'user_address', sanitize_text_field($_POST['user_address']));
        update_user_meta($user_id, 'user_city', sanitize_text_field($_POST['user_city']));
        update_user_meta($user_id, 'user_postal', sanitize_text_field($_POST['user_postal']));
        update_user_meta($user_id, 'user_country', sanitize_text_field($_POST['user_country']));
        update_user_meta($user_id, 'user_phone', sanitize_text_field($_POST['user_phone']));
    }
    add_action('personal_options_update', 'save_custom_user_profile_fields');
    add_action('edit_user_profile_update', 'save_custom_user_profile_fields');

    function custom_login_redirect($redirect_to, $request, $user)
    {
        if (isset($user->roles) && is_array($user->roles)) {
            if (in_array('administrator', $user->roles)) {
                return admin_url();
            } else {
                return home_url();
            }
        } else {
            return $redirect_to;
        }
    }
    add_filter('login_redirect', 'custom_login_redirect', 10, 3);






    add_action('wp_ajax_get_updated_totals', 'get_updated_totals');
    add_action('wp_ajax_nopriv_get_updated_totals', 'get_updated_totals');
    function get_updated_totals()
    {
        wp_send_json(array(
            'subtotal' => WC()->cart->get_cart_subtotal(),
            'shipping' => WC()->cart->get_cart_shipping_total(),
            'total' => WC()->cart->get_total()
        ));
    }





    add_filter('woocommerce_default_address_fields', 'make_shipping_fields_optional');

    function make_shipping_fields_optional($fields)
    {
        // Ensure Address line 2 exists and is not mandatory across all forms
        if (isset($fields['address_2'])) {
            $fields['address_2']['label'] = __('Address line 2', 'afb-offcanvas');
            $fields['address_2']['placeholder'] = __('Apartment, floor, access code, etc.', 'afb-offcanvas');
            $fields['address_2']['required'] = false; // WC default is optional; enforce for consistency
        }

        // Keep existing optional settings for other fields
        if (isset($fields['state'])) {
            $fields['state']['required'] = false;
        }
        if (isset($fields['postcode'])) {
            $fields['postcode']['required'] = false;
        }
        if (isset($fields['country'])) {
            $fields['country']['required'] = false;
        }
        if (isset($fields['city'])) {
            $fields['city']['required'] = false;
        }

        if (isset($fields['shipping_address_1'])) {
            $fields['shipping_address_1']['required'] = false;
        }

        return $fields;
    }










    // Capture lost password submission
    add_action('template_redirect', function () {
        if (isset($_POST['wc_reset_password']) && $_POST['wc_reset_password'] === 'true') {
            $login = isset($_POST['user_login']) ? sanitize_text_field(wp_unslash($_POST['user_login'])) : '';

            if (!username_exists($login) && !email_exists($login)) {
                // Save custom error for later display
                set_transient('custom_lostpassword_error', __('Invalid username or email address.', 'woocommerce'), 30);
            }
        }
    });

    // Display error above the form
    add_action('woocommerce_before_lost_password_form', function () {
    ?>
        <style>
            [type="submit"].woocommerce-Button.button {
                width: max-content !important
            }
        </style>
        <?php

        if ($msg = get_transient('custom_lostpassword_error')) {
            delete_transient('custom_lostpassword_error');
            echo '<div class="custom-lostpassword-errors" style="color:#b81c23; margin-bottom:15px;">';
            echo '<p>' . esc_html($msg) . '</p>';
            echo '</div>';
        }
    });






    add_action('init', function () {
        if ($_SERVER['REQUEST_URI'] === '/logout-user') {
            if (is_user_logged_in()) {
                wp_logout();
            }
            wp_redirect(home_url()); // Redirect to homepage after logout
            exit;
        }
    });





    add_action("wp_footer", function () {
        ?>
        <script>
            (function() {
                const targetIds = ['shipping_postcode_field', 'shipping_city_field'];

                function processField(field) {
                    if (!field) return;

                    // Remove existing .optional span
                    const optional = field.querySelector('.optional');
                    if (optional) optional.remove();

                    // Add required asterisk if not present
                    const label = field.querySelector('label');
                    if (label && !label.querySelector('.required')) {
                        const requiredSpan = document.createElement('span');
                        requiredSpan.className = 'required';
                        requiredSpan.setAttribute('aria-hidden', 'true');
                        requiredSpan.textContent = '*';
                        label.appendChild(requiredSpan);
                    }

                    // Mark the input as required
                    const input = field.querySelector('input');
                    if (input && !input.hasAttribute('required')) {
                        input.setAttribute('required', 'required');
                    }
                }

                function checkAndProcess() {
                    targetIds.forEach(id => {
                        const el = document.getElementById(id);
                        if (el) processField(el);
                    });
                }

                // Run initially  
                checkAndProcess();


                const observer = new MutationObserver(mutations => {
                    for (const mutation of mutations) {
                        for (const node of mutation.addedNodes) {
                            if (!(node instanceof HTMLElement)) continue;


                            if (targetIds.includes(node.id)) processField(node);


                            targetIds.forEach(id => {
                                const found = node.querySelector && node.querySelector(`#${id}`);
                                if (found) processField(found);
                            });
                        }
                    }
                });

                observer.observe(document.body, {
                    childList: true,
                    subtree: true
                });
            })();
        </script>
    <?php
    });






    add_shortcode('afb_show_username', function () {
        if (is_user_logged_in()) {
            $user = wp_get_current_user();
            $display = isset($user->display_name) ? $user->display_name : '';
            if ($display === '') {
                $first = get_user_meta($user->ID, 'first_name', true);
                $last  = get_user_meta($user->ID, 'last_name', true);
                $combo = trim(($first ?: '') . ' ' . ($last ?: ''));
                $display = $combo !== '' ? $combo : (isset($user->user_login) ? $user->user_login : '');
            }
            return esc_html($display);
        }

        // Not logged in: return Hebrew "התחברות"
        return esc_html(__('התחברות', 'afb-offcanvas'));
    });



    add_action('woocommerce_admin_order_data_after_order_details', function ($order) {
        if (!$order || !method_exists($order, 'get_meta')) {
            return;
        }

        $is_flag = (bool) $order->get_meta('afb_is_multiship');
        if (!$is_flag) { $is_flag = (bool) get_post_meta($order->get_id(), 'afb_is_multiship', true); }
        $delivery_option = (string) $order->get_meta('afb_delivery_option');
        if ($delivery_option === '') { $delivery_option = (string) get_post_meta($order->get_id(), 'afb_delivery_option', true); }
        $is_multiship = ($is_flag || $delivery_option === 'multiship');
        if (!$is_multiship) {
            return;
        }

    ?>

        <style>
            tr:has(.order-actions--heading),
            select.thwma-order-status {
                display: none !important;
            }

            .afb-order-itemmeta-display {
                display: block !important
            }

            #order_data>div.order_data_column_container>div:nth-child(3)>*:not(.order-delivery-option) {
                display: none !important;
            }
        </style>
    <?php

    }, 10, 1);

    add_action('woocommerce_admin_order_data_after_order_details', function ($order) {
        if (!$order || !method_exists($order, 'get_meta')) {
            return;
        }
        $delivery_option = (string) $order->get_meta('afb_delivery_option');
        if ($delivery_option === '') { $delivery_option = (string) get_post_meta($order->get_id(), 'afb_delivery_option', true); }
        if ($delivery_option !== 'pickup') {
            return;
        }
    ?>
        <style>
            #order_data>div.order_data_column_container>div:nth-child(3)>*:not(.order-delivery-option) {
                display: none !important;
            }
        </style>
    <?php
    }, 10, 1);

    add_action("wp_footer", function () {
    ?>

        <style>
            tr:has(.order-actions--heading) {
                display: none !important;
            }
        </style>

    <?php
    });


    add_action("admin_head", function () {
    ?>

        <style>
            tr:has(.order-actions--heading),
            select.thwma-order-status,
            select.thwma-order-status+.select2 {
                display: none !important;
            }

            .afb-order-itemmeta-display {
                display: none
            }
        </style>


<?php
    });
}




// add_filter( 'wp_mail', function( $args ) {
//     $args['headers'][] = 'Reply-To: contact@damyel.co.il';

//     return $args;
// });




add_filter('auth_cookie_expiration', function ($expiration, $user_id, $remember) {
    
    return 90 * DAY_IN_SECONDS;

}, 10, 3);








