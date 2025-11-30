<?php defined('ABSPATH') || exit;



// Detect Elementor edit mode to avoid interfering with the editor UI
$afb_is_elementor = (
    isset($_GET['elementor']) || isset($_GET['elementor-preview']) ||
    (class_exists('Elementor\\Plugin') && isset(\Elementor\Plugin::$instance) &&
     method_exists(\Elementor\Plugin::$instance->editor, 'is_edit_mode') &&
     \Elementor\Plugin::$instance->editor->is_edit_mode())
); 

//only load when not elementor
if (!$afb_is_elementor) {
	
	
	
 //load files
require_once AFB_OFFCANVAS_DIR . 'templates/checkout/terms-n-conditions-modal.php';


error_reporting(E_ALL);
@ini_set("display_errors", 1);

add_filter('woocommerce_checkout_fields', 'simplify_checkout_fields');
function simplify_checkout_fields($fields) {
    unset($fields['billing']['billing_company']);
    // Enforce phone required and postcode optional across billing/shipping
    foreach (['billing', 'shipping'] as $sec) {
        $phone_key = $sec . '_phone';
        if (isset($fields[$sec][$phone_key])) {
            $fields[$sec][$phone_key]['required'] = true;
        }
        $postcode_key = $sec . '_postcode';
        if (isset($fields[$sec][$postcode_key])) {
            $fields[$sec][$postcode_key]['required'] = false;
        }
    }
    return $fields;
}

add_filter('woocommerce_is_checkout', '__return_true');

// Alternatively, you could use the plugin's filter
add_filter('thwma_force_enqueue_public_scripts', '__return_true');

wp_enqueue_script('wc-checkout');

add_filter('woocommerce_is_checkout', '__return_true');

// Also ensure other WC functions work
WC()->frontend_includes();
if (!defined('WOOCOMMERCE_CHECKOUT')) {
    define('WOOCOMMERCE_CHECKOUT', true);
}



add_action('wp_enqueue_scripts', 'force_payment_gateway_scripts');
function force_payment_gateway_scripts() {
    if (class_exists('WC_Payment_Gateways')) {
        $gateways = WC()->payment_gateways->get_available_payment_gateways();
        foreach ($gateways as $gateway) {
            if (method_exists($gateway, 'payment_scripts')) {
                $gateway->payment_scripts();
            }
        }
    }
}




add_action('wp_enqueue_scripts', 'force_thwma_script_global');

function force_thwma_script_global()
{
    if (wp_script_is('thwma-public-script', 'registered')) {

        wp_enqueue_script('thwma-public-script');


    }
}
?>

<style>
.woocommerce-form-coupon-toggle,
	form.checkout_coupon.woocommerce-form-coupon,
	.woocommerce-notices-wrapper{
		display: none !important
	}
</style>
<?php

// Check if we're on the order-pay page and hide the checkout panel
if (is_wc_endpoint_url('order-pay')) {
    ?>
    <div id="afb-checkout-panel" class="afb-panel afb-checkout-panel" aria-hidden="true" role="dialog" >
        <div class="afb-panel__overlay" data-afb-close></div>
        <aside class="afb-panel__sheet" dir="<?php echo is_rtl() ? 'rtl' : 'ltr'; ?>">
            <div class="afb-panel__body" style="display: flex; align-items: center; justify-content: center; height: 100vh; text-align: center;">
                <div>
                    <h2><?php esc_html_e("Vous êtes sur la page de paiement", "afb-offcanvas"); ?></h2>
                    <p><?php esc_html_e("Nous ne pouvons pas accéder au panier ici", "afb-offcanvas"); ?></p>
                </div>
            </div>
        </aside>
    </div>
    <?php
} else {
    // Normal checkout panel content
    ?>

<div id="afb-checkout-panel" class="afb-panel afb-checkout-panel" aria-hidden="true" role="dialog"
    aria-label="<?php esc_attr_e('Commande', 'afb-offcanvas'); ?>">
    <div class="afb-panel__overlay" data-afb-close></div>
    <aside class="afb-panel__sheet" dir="<?php echo is_rtl() ? 'rtl' : 'ltr'; ?>">
        <div class="afb-topbar">
			<div>
				
			</div>
            <div class="afb-topbar__title"><?php esc_html_e('MON PANIER', 'afb-offcanvas'); ?> |
                <?php esc_html_e('TOTAL', 'afb-offcanvas'); ?>:
                <?php echo function_exists('WC') ? WC()->cart->get_total() : ''; ?>
            </div>
            <button class="afb-topbar__close afb-new-close" type="button"
                data-afb-close>
				<span class='desktop'><?php esc_html_e('FERMER', 'afb-offcanvas'); ?></span>
				<span class='mobile'>
					<svg width="15px" height="15px" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
<path fill-rule="evenodd" clip-rule="evenodd" d="M5.29289 5.29289C5.68342 4.90237 6.31658 4.90237 6.70711 5.29289L12 10.5858L17.2929 5.29289C17.6834 4.90237 18.3166 4.90237 18.7071 5.29289C19.0976 5.68342 19.0976 6.31658 18.7071 6.70711L13.4142 12L18.7071 17.2929C19.0976 17.6834 19.0976 18.3166 18.7071 18.7071C18.3166 19.0976 17.6834 19.0976 17.2929 18.7071L12 13.4142L6.70711 18.7071C6.31658 19.0976 5.68342 19.0976 5.29289 18.7071C4.90237 18.3166 4.90237 17.6834 5.29289 17.2929L10.5858 12L5.29289 6.70711C4.90237 6.31658 4.90237 5.68342 5.29289 5.29289Z" fill="#0F1729"/>
</svg>
				</span>
			</button>
			
			<style>
				.mobile{
						display: none !important
				}
				
				.desktop{
						display: inline-block !important
				}
				
				@media(max-width: 768px){
					.mobile{
						display: inline-block !important
					}
					.desktop{
						display: none !important
					}
					
					.afb-new-close svg{
						width: 18px !important;
						position: absolute !important;
						right: 10px !important;
						transform: translateY(-50%) !important;
					}
					
					.afb-topbar__title {
						margin-bottom: -5px !important
					}
				}
			</style>
        </div>
        <nav class="afb-steps" aria-label="<?php esc_attr_e('Étapes', 'afb-offcanvas'); ?>">
            <ol>
                <li data-step="auth" <?php if (!is_user_logged_in()): ?> class="is-current" <?php else: ?>
                        style="pointer-events:none" <?php endif; ?>>
                    <?php esc_html_e('Authentification', 'afb-offcanvas'); ?>
                </li>
                <li data-step="choice" <?php echo is_user_logged_in() ? 'class="is-current"' : 'style="pointer-events:none"'; ?>>
                    <?php esc_html_e('Vous souhaitez', 'afb-offcanvas'); ?>
                </li>
                <li data-step="address" style="pointer-events:none">
                    <?php esc_html_e('Livraison', 'afb-offcanvas'); ?>
                </li>
                <li data-step="payment" style="pointer-events:none">
                    <?php esc_html_e('Payment', 'afb-offcanvas'); ?>
                </li>
            </ol>
        </nav>
		
		
		<div class="afb-wc-errors">
			
		</div>

        <div class="afb-panel__body" data-loggedin="<?php echo is_user_logged_in() ? '1' : '0'; ?>">
            <?php if (!is_user_logged_in()): ?>
                <section class="afb-step" data-step-panel="auth">
                    <div class="afb-auth-grid">

                       <div class="afb-auth-card-">
							<div class="form-box">
								<h2 class="form-title"><?php esc_html_e("J'ai déjà un compte, je me connecte ici", "afb-offcanvas"); ?></h2>

								<div id="login-message" class="form-message" style="display: none;"></div>

								<form action="<?php echo esc_url(site_url('wp-login.php')); ?>" method="post" id="loginform">
									<div class="form-group">
										<label class="form-label" for="user_login"><?php esc_html_e("Email", "afb-offcanvas"); ?> <span class="required">*</span></label>
										<input type="email" name="log" id="user_login" class="form-input" required value="<?php echo esc_attr($user_login ?? ''); ?>">
									</div>

									<div class="form-group">
										<label class="form-label" for="user_pass"><?php esc_html_e("Mot de passe", "afb-offcanvas"); ?> <span class="required">*</span></label>
										<div class="input-wrapper">
											<input type="password" name="pwd" id="user_pass" class="form-input" required>
											<button type="button" class="password-toggle" onclick="togglePassword('user_pass', this)">
												<?php esc_html_e("Montrer", "afb-offcanvas"); ?>
											</button>
										</div>
									</div>

									<input type="hidden" name="redirect_to" value="<?php echo esc_url( home_url( add_query_arg( 'open_afb_cart', 'true', $_SERVER['REQUEST_URI'] ) ) ); ?>">
									<input type="hidden" name="testcookie" value="1">
<!-- <input type="checkbox" name="rememberme" value="forever"> -->
									<button type="submit" name="wp-submit" class="form-submit"><?php esc_html_e("Se connecter", "afb-offcanvas"); ?></button>

									<?php wp_nonce_field('ajax-login-nonce', 'security'); ?>
									
									<div class="form-link">
										<a href="<?php echo esc_url(wp_lostpassword_url()); ?>" rel="nofollow">
											<?php esc_html_e("Mot de passe oublié?", "afb-offcanvas"); ?>
										</a>
									</div>
								</form>
							</div>
						</div>


                        <div class="afb-auth-card-">
							<div class="form-box">
								<h2 class="form-title"><?php esc_html_e("Je n'ai pas de compte, j'en crée un ici", "afb-offcanvas"); ?></h2>

								<div id="register-message" class="form-message" style="display: none;"></div>

								<form action="<?php echo esc_url(site_url('wp-login.php?action=register')); ?>" method="post" id="registerform">
									<div class="form-group">
										<label class="form-label" for="user_lastname"><?php esc_html_e("Nom", "afb-offcanvas"); ?> <span class="required">*</span></label>
										<input type="text" name="user_lastname" id="user_lastname" class="form-input" required>
									</div>

									<div class="form-group">
										<label class="form-label" for="user_firstname"><?php esc_html_e("Prénom", "afb-offcanvas"); ?> <span class="required">*</span></label>
										<input type="text" name="user_firstname" id="user_firstname" class="form-input" required>
									</div>

									<div class="form-group">
										<label class="form-label" for="user_email"><?php esc_html_e("Email", "afb-offcanvas"); ?> <span class="required">*</span></label>
										<input type="email" name="user_email" id="user_email" class="form-input" required>
									</div>

									<div class="form-group">
										<label class="form-label" for="user_password"><?php esc_html_e("Mot de passe", "afb-offcanvas"); ?> <span class="required">*</span></label>
										<div class="input-wrapper">
											<input type="password" name="user_password" id="user_password" class="form-input" required>
											<button type="button" class="password-toggle" onclick="togglePassword('user_password', this)">
												<?php esc_html_e("Montrer", "afb-offcanvas"); ?>
											</button>
										</div>
									</div>

<!-- 									<div class="form-group">
										<label class="form-label" for="user_address"><?php esc_html_e("Phone", "afb-offcanvas"); ?> <span class="required">*</span></label>
										<input type="text" name="shipping_phone" id="shipping_phone" class="form-input address-input" required>
									</div> -->
									
									
									<div class="form-group">
										<label class="form-label" for="user_address"><?php esc_html_e("Adresse", "afb-offcanvas"); ?> <span class="required">*</span></label>
										<input type="text" name="user_address" id="user_address" class="form-input address-input" required>
									</div>

									<div class="form-group">
										<label class="form-label" for="user_city"><?php esc_html_e("Ville", "afb-offcanvas"); ?> <span class="required">*</span></label>
										<input type="text" name="user_city" id="user_city" class="form-input" required>
									</div>

									<div class="form-group">
    <label class="form-label" for="user_postal"><?php esc_html_e("Code postal", "afb-offcanvas"); ?></label>
    <input type="text" name="user_postal" id="user_postal" class="form-input">
</div>

									<div class="form-group">
										<label class="form-label" for="user_country"><?php esc_html_e("Pays", "afb-offcanvas"); ?> <span class="required">*</span></label>
										<select name="user_country" id="user_country" class="country-select" required>
											<option value=""><?php esc_html_e("Choisir votre pays", "afb-offcanvas"); ?></option>
											<option value="FR"><?php esc_html_e("France", "afb-offcanvas"); ?></option>
											<option value="BE"><?php esc_html_e("Belgique", "afb-offcanvas"); ?></option>
											<option value="CH"><?php esc_html_e("Suisse", "afb-offcanvas"); ?></option>
											<option value="CA"><?php esc_html_e("Canada", "afb-offcanvas"); ?></option>
											<option value="US"><?php esc_html_e("États-Unis", "afb-offcanvas"); ?></option>
											<option value="DE"><?php esc_html_e("Allemagne", "afb-offcanvas"); ?></option>
											<option value="ES"><?php esc_html_e("Espagne", "afb-offcanvas"); ?></option>
											<option value="IL"><?php esc_html_e("Israel", "afb-offcanvas"); ?></option> 
											<option value="IT"><?php esc_html_e("Italie", "afb-offcanvas"); ?></option>
											<option value="UK"><?php esc_html_e("Royaume-Uni", "afb-offcanvas"); ?></option>
										</select>
									</div>

									<div class="form-group">
										<label  style="margin-bottom: 3px;"  class="form-label" for="user_phone"><?php esc_html_e("Téléphone", "afb-offcanvas"); ?> <span class="required">*</span></label>
										<input type="tel" name="user_phone" id="user_phone" class="form-input afb_phone"  required>
									</div>

									
									
									<?php 
										wp_nonce_field("create_user_nonce", "create_user_nonce_field");  
									?>

									<button type="submit" name="wp-submit" class="form-submit"><?php esc_html_e("Créez votre compte", "afb-offcanvas"); ?></button>
								</form>
							</div>
						</div>


                        <script>
                            function togglePassword(inputId, button) {
                                const input = document.getElementById(inputId);
                                const isPassword = input.type === 'password';

                                input.type = isPassword ? 'text' : 'password';
                                button.textContent = isPassword ? 'Cacher' : 'Montrer';
                            }

                            function showMessage(elementId, message, isError = false) {
                                const messageElement = document.getElementById(elementId);
                                messageElement.innerHTML = message;
                                messageElement.className = isError ? 'form-message error' : 'form-message success';
                                messageElement.style.display = 'block';

                                setTimeout(() => {
                                    messageElement.style.display = 'none';
                                }, 15000);
                            }

                            document.getElementById('loginform').addEventListener('submit', function (e) {
                                e.preventDefault();

                                const formData = new FormData(this);
								formData.append("action", "ajax_login");  

								fetch("<?php echo admin_url('admin-ajax.php'); ?>", {
									method: "POST",
									body: formData,
									credentials: "same-origin"
								})
								.then(res => res.json())
								.then(data => {
									if (data.success) {
										showMessage('login-message', '<?php echo esc_js( esc_html__( "Connexion réussie! Redirection...", "afb-offcanvas" ) ); ?>', false);

										const redirectUrl =  data.data.redirect || formData.get("redirect_to");
										setTimeout(() => {
											if (redirectUrl) {
												window.location.href = redirectUrl;
											} else {
												window.location.reload();
											}
										}, 1000);
									} else {
										// Server sent an error JSON
										showMessage('login-message',   data.data.message, true);
									}
								})
								.catch(error => {
									// Network or unexpected error
									showMessage('login-message',  error.message, true);
								});

                            });
							
							
							

							
							/**
							 * 
							 * REGISTRATION
							 * 
							 * */
							
							
                            document.getElementById('registerform').addEventListener('submit', function (e) {
                                e.preventDefault();

                                const formData = new FormData(this);
                                // Enforce phone requirement on registration
                                const userPhone = (formData.get('user_phone') || '').trim();
                                if (!userPhone) {
                                    showMessage('register-message', '<?php echo esc_js( esc_html__( "Le téléphone est requis pour l\'inscription.", "afb-offcanvas" ) ); ?>', true);
                                    return;
                                }

                                const userData = {
                                    action: 'custom_user_registration',
                                    user_login: formData.get('user_email'),
                                    user_email: formData.get('user_email'),
                                    user_pass: formData.get('user_password'),
                                    first_name: formData.get('user_firstname'),
                                    last_name: formData.get('user_lastname'),
                                    user_address: formData.get('user_address'),
                                    user_city: formData.get('user_city'),
                                    user_postal: formData.get('user_postal'),
                                    user_country: formData.get('user_country'),
                                    user_phone: userPhone,
                                    nonce: formData.get('create_user_nonce_field')
                                };

                                fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
                                    method: 'POST',
                                    headers: {
                                        'Content-Type': 'application/x-www-form-urlencoded',
                                    },
                                    body: new URLSearchParams(userData)
                                })
                                    .then(response => response.json())
                                    .then(data => {
                                        if (data.success) {
                                            showMessage('register-message', '<?php echo esc_js( esc_html__( "Compte créé avec succès! Vous pouvez maintenant vous connecter.", "afb-offcanvas" ) ); ?>', false);
                                            document.getElementById('registerform').reset();
											
											setTimeout(() => {
                                                    window.location.reload();
                                                }, 1000);
											
// 											jQuery(document.body).trigger('update_checkout');
                                        } else {
                                            showMessage('register-message', '<?php echo esc_js( esc_html__( "Erreur: ", "afb-offcanvas" ) ); ?>' + (data.data || '<?php echo esc_js( esc_html__( "Une erreur est survenue", "afb-offcanvas" ) ); ?>'), true);
                                        }
                                    })
                                    .catch(error => {
                                        console.error('Error:', error);
                                        showMessage('register-message', '<?php echo esc_js( esc_html__( "Une erreur est survenue lors de la création du compte", "afb-offcanvas" ) ); ?>', true);
                                    });
                            });
							
							
							
							

							

                        </script>
                    </div>
                </section>
            <?php endif; ?>

            <section class="afb-step step_choice <?php echo is_user_logged_in() ? '' : 'is-hidden'; ?>" data-step-panel="choice">
                <?php if (is_user_logged_in()): ?>
                    <div class="afb-muted afb-curr-user-log" style="text-align: center;
    font-size: 10px;"><?php esc_html_e('Vous êtes connecté en tant que', 'afb-offcanvas'); ?>
                        <?php echo wp_get_current_user()->display_name; ?>
                    </div>
                    <br>
                <?php endif; ?>
                <div class="afb-choice">
                    <button class="afb-choice__item"
                        data-option="me"><span><?php esc_html_e('LIVRER CHEZ MOI', 'afb-offcanvas'); ?></span>
                    </button>
                    <button class="afb-choice__item"
                        data-option="other"><span><?php esc_html_e("LIVRER CHEZ QUELQU'UN D'AUTRE", 'afb-offcanvas'); ?></span>
                    </button>
                    <button class="afb-choice__item"
                        data-option="multiship"><span><?php esc_html_e('ENVOYER À PLUSIEURS ADRESSES', 'afb-offcanvas'); ?></span>
                    </button>
                    <button class="afb-choice__item"
                        data-option="pickup"><span><?php esc_html_e('RETRAIT GRATUIT DANS UNE BOUTIQUE', 'afb-offcanvas'); ?></span>
                    </button>
                </div>
				
				
				<div class="delivery_info_section">
					<style>
						.delivery_info_section{
							font-size: 12px 
						}
						
						.delivery_info_section p{
							margin-bottom: 4px !important
						}
					</style>
					<?php
						// Check if WPML is active
						$wpml_active = function_exists('icl_object_id') && function_exists('icl_get_languages');
						
						if ($wpml_active) {
							// Get current language
							$current_language = apply_filters('wpml_current_language', null);
							// Get delivery info for current language
							$delivery_info = get_option('delivery_information_' . $current_language, '');
							
							// Fallback to default language if empty
							if (empty($delivery_info)) {
								$default_language = apply_filters('wpml_default_language', null);
								$delivery_info = get_option('delivery_information_' . $default_language, '');
							}
						} else {
							// Fallback for when WPML is not active
							$delivery_info = get_option('delivery_information', '');
						}
						
						echo wpautop(wp_kses_post($delivery_info));
					?>
					
				</div>
            </section>

            <section class="afb-step is-hidden afb-address-step" data-step-panel="address">
                <!-- <form class="afb-checkout-form"> -->
				
				
					  
				<?php
					if ( function_exists( 'WC' ) ) {

						if ( ! class_exists( 'WC_Checkout' ) ) {
							include_once WC_ABSPATH . 'includes/class-wc-checkout.php';
						}

						$checkout = new WC_Checkout();
					$checkout->get_checkout_fields();

						if ( $checkout ) {
							do_action( 'woocommerce_before_checkout_form', $checkout );

							// This outputs the actual form, same as default checkout
					//         woocommerce_checkout_form();

					//         do_action( 'woocommerce_after_checkout_form', $checkout );
						} else {
							echo "<pre style='color:red;'>Checkout object not available.</pre>";
						}
					}
 
				?>
				 
                <form name="checkout" method="post" class="checkout woocommerce-checkout afb-checkout-form"
                    action="<?php echo esc_url(wc_get_checkout_url()); ?>" enctype="multipart/form-data">
					
					<?php 
					
					wp_nonce_field( 'woocommerce-process_checkout', 'woocommerce-process-checkout-nonce' );
					do_action( 'woocommerce_checkout_process' );

					?>
					
					<!-- Hidden field to capture delivery option -->
					<input type="hidden" id="afb_delivery_option" name="afb_delivery_option" value="" />
					
					

					
					<?php //do_action( 'woocommerce_checkout_before_customer_details' ); ?>
					
					
                    <h2 class="afb-section-title afb-address-step-title">
<!-- 						//comes from JS -->
					</h2>
					

                    <div class="afb-checkout-grid">
                       <div class="afb-checkout-column">
							<h3 class="afb-subtitle afb-payment-step-title">
								
								<span class="all-steps">
									<?php esc_html_e("Vos coordonnées de facturation", "afb-offcanvas"); ?>
								</span>
								
								
								<span class="first-step">
									<?php esc_html_e("Vos informations de facturation et d'expédition", "afb-offcanvas"); ?>
								</span>
								
						   </h3>

							<div class="form-group">
								<label class="form-label" for="billing_first_name"><?php esc_html_e("Prénom", "afb-offcanvas"); ?> <span class="required">*</span></label>
								<input type="text" name="billing_first_name" id="billing_first_name" class="form-input" required>
							</div>

							<div class="form-group">
								<label class="form-label" for="billing_last_name"><?php esc_html_e("Nom", "afb-offcanvas"); ?> <span class="required">*</span></label>
								<input type="text" name="billing_last_name" id="billing_last_name" class="form-input" required>
							</div>

							<div class="form-group">
								<label class="form-label" for="billing_company"><?php esc_html_e("Société", "afb-offcanvas"); ?></label>
								<input type="text" name="billing_company" id="billing_company" class="form-input">
							</div>

							<div class="form-group">
							<label class="form-label" for="billing_address_1"><?php esc_html_e("Adresse", "afb-offcanvas"); ?> <span class="required">*</span></label>
							<input type="text" name="billing_address_1" id="billing_address_1" class="form-input" required>
						</div>

						<div class="form-group">
							<label class="form-label" for="billing_address_2"><?php esc_html_e("Address line 2", "afb-offcanvas"); ?></label>
							<input type="text" name="billing_address_2" id="billing_address_2" class="form-input" placeholder="<?php echo esc_attr(__('Apartment, floor, access code, etc.', 'afb-offcanvas')); ?>">
						</div>

							<div class="form-group">
								<label class="form-label" for="billing_postcode"><?php esc_html_e("Code postal", "afb-offcanvas"); ?> <span class="required">*</span></label>
								<input type="text" name="billing_postcode" id="billing_postcode" class="form-input" required>
							</div>

							<div class="form-group">
								<label class="form-label" for="billing_city"><?php esc_html_e("Ville", "afb-offcanvas"); ?> <span class="required">*</span></label>
								<input type="text" name="billing_city" id="billing_city" class="form-input" required>
							</div>

							<!-- 
							<div class="form-group">
								<label class="form-label" for="billing_state"><?php esc_html_e("Région / État", "afb-offcanvas"); ?> <span class="required">*</span></label>
								<input type="text" name="billing_state" id="billing_state" class="form-input" required>
							</div> 
							-->

							<div class="form-group">
								<label class="form-label" for="billing_country"><?php esc_html_e("Pays", "afb-offcanvas"); ?> <span class="required">*</span></label>
								<select name="billing_country" id="billing_country" class="country-select" required>
									<option value=""><?php esc_html_e("Choisissez votre pays", "afb-offcanvas"); ?></option>
									<?php
									$allowed_countries = WC()->countries->get_allowed_countries();
									foreach ($allowed_countries as $code => $name) {
										echo '<option value="' . esc_attr($code) . '">' . esc_html($name) . '</option>';
									}
									?>
								</select>
							</div>

							<div class="form-group">
								<label class="form-label" for="billing_phone"><?php esc_html_e("Téléphone", "afb-offcanvas"); ?> <span class="required">*</span></label>
								<input type="tel" name="billing_phone" id="billing_phone" class="form-input" required>
							</div>

							<div class="form-group">
								<labelclass="form-label" for="billing_email"><?php esc_html_e("Email", "afb-offcanvas"); ?> <span class="required">*</span></label>
								<input type="email" name="billing_email" id="billing_email" class="form-input" required>
							</div>
						</div>


                        <div class="afb-checkout-column">

							  <div class="afb-pickup-section is-hidden">
								<h3 class="afb-subtitle"><?php esc_html_e("POINT DE RETRAIT", "afb-offcanvas"); ?> </h3>
								<div class="form-group">
								  <label class="form-label" for="pickup_location"><?php esc_html_e("BOUTIQUE", "afb-offcanvas"); ?> <span class="required">*</span></label>
								  <select name="pickup_location" id="pickup_location" class="form-input" >
									<option value="">-- <?php esc_html_e(" Choisissez une boutique", "afb-offcanvas"); ?> --</option>
								  </select>
								</div>
							  </div>


                           <div class="afb-order-review" id="afb-order-review">
							<?php do_action( 'woocommerce_checkout_before_order_review_heading' ); ?>
							<?php do_action( 'woocommerce_checkout_before_order_review' ); ?>

							<h3 class="afb-subtitle"><?php esc_html_e("Votre commande", "afb-offcanvas"); ?></h3>

							<!-- Loading indicator -->
							<div class="afb-order-loading" style="display: none;">
								<p><?php esc_html_e("Mise à jour de votre commande...", "afb-offcanvas"); ?></p>
							</div>

							<?php 
							// Call the function from afb-wc-ajax.php - this renders directly without wrapper
							if (function_exists('afb_render_order_review_content')) {
								echo afb_render_order_review_content();
							}
							?>
						</div>

<!-- Split Items Button for Multiship -->
<div class="afb-split-items-container" id="afb-split-items-container" style="display: none;">
	<button type="button" class="afb-split-items-btn" id="afb-split-items-btn">
		<?php esc_html_e("Diviser les articles", "afb-offcanvas"); ?>
	</button>
</div>
							
							<style>
								#order_comments {
/* 									border: 1px solid rgba(0, 0, 0, .2) !important; */
									border: 1px solid #1d1d1b !important;
									min-height: 77px !important;
									padding: 10px !important
								}
								
								.afb-split-items-container {
									margin: 15px 0;
									text-align: center;
								}
								
								.afb-split-items-btn {
									background-color: #1d1d1b;
									color: white;
									border: none;
									padding: 12px 24px;
									border-radius: 4px;
									cursor: pointer;
									font-size: 14px;
									font-weight: 500;
									transition: background-color 0.3s ease;
								}
								
								.afb-split-items-btn:hover {
									background-color: #333;
								}
								
								.afb-split-items-btn:active,
								.afb-split-items-btn:focus{
									background-color: #000;
								}
							</style>
							




                            <h3 class="afb-subtitle"><?php esc_html_e("Adresse de livraison", "afb-offcanvas"); ?></h3>

                            <div class="afb-shipping-toggle">
                                <label>
                                    <input type="checkbox" id="ship-to-different-address"
                                        name="ship_to_different_address" value="1" 
                                        <?php checked(apply_filters('woocommerce_ship_to_different_address_checked', 'shipping' === get_option('woocommerce_ship_to_destination') ? 1 : 0), 1); ?>
               />
                                    <span><?php esc_html_e("Utiliser une adresse de livraison différente", "afb-offcanvas"); ?></span>
                                </label>

                            </div>

                            <div id="shipping-address-fields" class="is-hidden">

                                <link
                                    href="https://damyel.co.il/wp-content/plugins/woocommerce-multiple-addresses-pro/src/thpublic/assets/css/thwma-public.min.css?ver=6.8.2">


                                <script
                                    src="https://damyel.co.il/wp-content/plugins/woocommerce-multiple-addresses-pro/src/thpublic/assets/js/thwma-public.min.js"></script>


                                <?php
								//function_exists( 'is_checkout' ) && is_checkout() && ! is_order_received_page() &&
									if (  !is_wc_endpoint_url( 'order-pay' ) ) {
										
										do_action('woocommerce_checkout_shipping');
									}
								 
								?>
                                <?php
                                // This hook allows plugins to add their own shipping address fields
                                //do_action('woocommerce_before_checkout_shipping_form', $checkout);

                                // Output the standard shipping fields
// 								foreach ($checkout->get_checkout_fields('shipping') as $key => $field) {
// 									woocommerce_form_field($key, $field, $checkout->get_value($key));
// 								}
                                
                                // This hook allows plugins to add content after shipping fields
// 								do_action('woocommerce_after_checkout_shipping_form', $checkout);
                                ?>
								
								


								<style>
									.shipping_address{
										display: block !important; 
									}

									@media(min-width:1024px){
										.shipping_address{
											width: calc(100% + 15px) !important;
										}
									}
								</style>
                                <div class="form-group">
                                    <label class="form-label" for="shipping_first_name"><?php esc_html_e("Prénom", "afb-offcanvas"); ?> <span
                                            class="required">*</span></label>
                                    <input type="text" name="shipping_first_name" id="shipping_first_name"
                                        class="form-input" required>
                                </div>

                                <div class="form-group">
                                    <label class="form-label" for="shipping_last_name"><?php esc_html_e("Nom", "afb-offcanvas"); ?> <span
                                            class="required">*</span></label>
                                    <input type="text" name="shipping_last_name" id="shipping_last_name"
                                        class="form-input" required>
                                </div>

                                <div class="form-group">
                                    <label class="form-label" for="shipping_company"><?php esc_html_e("Société", "afb-offcanvas"); ?></label>
                                    <input type="text" name="shipping_company" id="shipping_company" class="form-input">
                                </div>

                                <div class="form-group">
                                    <label class="form-label" for="shipping_address_1"><?php esc_html_e("Adresse", "afb-offcanvas"); ?> <span
                                            class="required">*</span></label>
                                    <input type="text" name="shipping_address_1" id="shipping_address_1"
                                        class="form-input" required>
                                </div>

                                <div class="form-group">
                                    <label class="form-label" for="shipping_address_2"><?php esc_html_e("Address line 2", "afb-offcanvas"); ?></label>
                                    <input type="text" name="shipping_address_2" id="shipping_address_2" class="form-input" placeholder="<?php echo esc_attr(__('Apartment, floor, access code, etc.', 'afb-offcanvas')); ?>">
                                </div>
								
								
								<div class="form-group">
								<label class="form-label" for="shipping_phone"><?php esc_html_e("Téléphone", "afb-offcanvas"); ?> <span class="required">*</span></label>
								<input type="tel" name="shipping_phone" id="shipping_phone" class="form-input" required>
							</div>

                                <div class="form-group">
                                    <label class="form-label" for="shipping_postcode"><?php esc_html_e("Code postal", "afb-offcanvas"); ?> <span
                                            class="required">*</span></label>
                                    <input type="text" name="shipping_postcode" id="shipping_postcode"
                                        class="form-input" required>
                                </div>

                                <div class="form-group">
                                    <label class="form-label" for="shipping_city"><?php esc_html_e("Ville", "afb-offcanvas"); ?> <span
                                            class="required">*</span></label>
                                    <input type="text" name="shipping_city" id="shipping_city" class="form-input"
                                        required>
                                </div>

                                <?php
								// Get WooCommerce allowed countries
								$countries = WC()->countries->get_shipping_countries();
								?>

								<div class="form-group">
									<label class="form-label" for="shipping_country">
										<?php esc_html_e("Pays", "afb-offcanvas"); ?> <span class="required">*</span>
									</label>
									<select name="shipping_country" id="shipping_country" class="country-select" required>
										<option value=""><?php esc_html_e('Choisissez votre pays', 'afb-offcanvas'); ?></option>
										<?php foreach ($countries as $code => $name): ?>
											<option value="<?php echo esc_attr($code); ?>">
												<?php echo esc_html($name); ?>
											</option>
										<?php endforeach; ?>
									</select>
								</div>




                            </div>
							
							
							
							
							
						<div class="afb-extra-fields" style="margin-top:6px">
							<div class="">
								<div class="woocommerce-additional-fields__field-wrapper">
									
									<p class="form-row notes" id="order_comments_field" data-priority="">
										<label for="order_comments" class="form-label">
											<?php esc_html_e("Message Cadeau", "afb-offcanvas"); ?>
										</label>

										<span class="woocommerce-input-wrapper">
											
											<textarea name="order_comments" class="input-text " id="order_comments" rows="2" cols="5">
											</textarea>
										</span>

									</p>	 
								</div>
							</div>
						</div>

						<!-- Payment Methods Section moved from step 4 -->
						<div class="afb-payment-methods" style="margin-top: 20px;display:none !important">
							<h3 class="afb-subtitle"><?php esc_html_e("Paiement", "afb-offcanvas"); ?></h3>
							<?php
							// Add before your payment methods section
							WC()->payment_gateways();
							WC()->shipping();

							// Before rendering payment methods, add:
							do_action('woocommerce_review_order_before_payment');
							do_action('woocommerce_checkout_before_order_review_heading');
							
							if (WC()->payment_gateways()) {
								$available_gateways = WC()->payment_gateways->get_available_payment_gateways();

								if (!empty($available_gateways)) {
									// Get the first gateway to auto-select it
									$first_gateway_id = array_key_first($available_gateways);
									
									// Set the chosen payment method in WooCommerce session
									WC()->session->set('chosen_payment_method', $first_gateway_id);
									?>
									<div id="payment" class="woocommerce-checkout-payment">
										<ul class="wc_payment_methods payment_methods methods">
											<?php
											$is_first = true;
											foreach ($available_gateways as $gateway) {
												// Auto-check the first available gateway
												$is_checked = $is_first ? 'checked="checked"' : '';
												
												echo '<li class="wc_payment_method payment_method_' . esc_attr($gateway->id) . '">';
												echo '<input type="radio" name="payment_method" id="payment_method_' . esc_attr($gateway->id) . '" value="' . esc_attr($gateway->id) . '" ' . checked('true', 'true') . ' >';
												echo '<label for="payment_method_' . esc_attr($gateway->id) . '">' . $gateway->get_title() . '</label>';

												if ($gateway->has_fields() || $gateway->get_description()) {
													echo '<div class="payment_box payment_method_' . esc_attr($gateway->id) . '">';
													$gateway->payment_fields();
													echo '</div>';
												}

												echo '</li>';
												$is_first = false;
											}
											?>
										</ul>
									</div>
									
									<script>
									jQuery(document).ready(function($) {
										// Ensure the first payment method is selected
										var firstPaymentMethod = $('.wc_payment_methods input[type="radio"]:first');
										if (firstPaymentMethod.length && !$('.wc_payment_methods input[type="radio"]:checked').length) {
											firstPaymentMethod.prop('checked', true).trigger('change');
										}
									});
									</script>
									<?php
								} else {
									echo '<p>' . esc_html__("Désolé, il semble qu'il n'y ait pas de méthode de paiement disponible. Veuillez nous contacter si vous avez besoin d'aide.", "afb-offcanvas") . '</p>';
								}
							}
							?>
						</div>
							
							
							
<!-- 							/// custom terms check  -->
							<div>
								
								<style>
									.afb-check-label{
										display: flex;
										align-items: center;
										gap: 10px;
										margin-top: 20px
									}
									
									/* Hide default WooCommerce place order button */
									[name="woocommerce_checkout_place_order"] {
										display: none !important;
									}

                                    #payment{
                                        background-color: transparent;
                                    }
								</style>
								
								
								
								
								
								<label class="afb-check-label">
                                    <input type="checkbox" id="afb_terms" name="terms" value="1">
                                    <span> 
										<?php esc_html_e("Je suis d'accord avec les ", "afb-offcanvas"); ?>
										<a  href='#modal' id='terms-n-conditions'><?php esc_html_e("terms and conditions", "afb-offcanvas"); ?></a> 
										
										<?php esc_html_e("d'utilisations et j'y adhère sans condition.", "afb-offcanvas"); ?>
									</span>
                                </label>
								
								<p id="terms_alert" style="
								font-family: 'Sweet Sans On Air', sans-serif;
								font-size: 10px;
								letter-spacing: 2px;
								margin-bottom: 6px;
								font-weight: 500;
								line-height: 11px;
								color: red;
														   display:none
							">
									
									<?php esc_html_e("Note: Please accept terms and conditions...", "afb-offcanvas"); ?>
									
								</p>
								
							</div>
							
							
							

							
							
							
							
							
							<script>
								
// 								function showTermsAlert(e){
// 										e.preventDefault()
// 										document.querySelector("#terms_alert").style.display = "block"
// 									} 
								
								
// 								document.addEventListener("DOMContentLoaded", function () {
// 								  const checkbox = document.getElementById("afb_terms");
// 								  const button = document.querySelector(".afb-address-step .afb-next-button");

// 								  function toggleButton(e) {
// 									if (checkbox.checked) {
// 									  button.disabled = false; 
									
// 									  document.querySelector("#terms_alert").style.display = "none"
// 									} else {
// 									  button.disabled = true; 
// 										e.preventDefault()
// 									  document.querySelector("#terms_alert").style.display = "block"
// 									}
// 								  }

// 								  // Run on load
// 								  toggleButton();
 
								
								
								
								
							</script>


							

							
							
							

                                <!-- This hook is specifically for multiple shipping address plugins -->
                                <?php do_action('woocommerce_checkout_after_customer_details'); ?>
<?php do_action( 'woocommerce_after_checkout_form', $checkout ); ?>

                        </div>
                    </div>

                    <div class="afb-checkout-actions">
                        <button type="button" class="afb-back-button" data-prev-step="choice">
							<?php esc_html_e("Retour", "afb-offcanvas"); ?>
						</button>
                        <button type="submit" class="afb-submit-button" id="place_order">
							<?php esc_html_e("Payer maintenant", "afb-offcanvas"); ?>
						</button>
                    </div>
                </form>
            </section>


            <section class="afb-step is-hidden" data-step-panel="payment">
                <!-- This step is just for visual indication in tabs, no actual content -->
            </section>

        </div>
    </aside>
</div>

<?php
} // End of else condition for normal checkout panel
?>



<link href="https://damyel.co.il/wp-content/plugins/WCGatewayTranzila/css/checkout.css?ver=0.0.24.6" />
<script src="https://damyel.co.il/wp-content/plugins/WCGatewayTranzila/js/hf.js?ver=0.0.24.6"></script>
<script src="https://hf.tranzila.com/assets/js/thostedf.js?ver=0.0.24.6"></script>






<style>
	
	button.iti__selected-country:focus,
	button.iti__selected-country:hover{
		background: transparent !important
	}
	
	[name="ship_to_multi_address"], [name="ship_to_multi_address"] + font{
		display: none !important
	}
	/* 	//panel-checkout.php css */
	.woocommerce-NoticeGroup-updateOrderReview{
		display:none !important
	}
	[name="ship_to_multi_address"]{
		margin-right: 10px !important;
		bottom: -3px !important;
    	position: relative !important;
	}
	
.multi-shipping-table{
	display:table !important
}

#thwma_cart_multi_shipping_display{
	display: block !important
}
	
	h3#ship-to-different-address,
	.woocommerce-additional-fields{
		display:none !important
	}
	
	.afb-checkout-column:has(> .afb-shipping-toggle.is-hidden) > .afb-subtitle {
		display: none !important;
	}
	
    .thwma_cart_shipping_button {
        font-weight: bold !important;
        background: black !important;
        color: white !important;
        display: inline-block !important;
        padding: 10px 20px !important;
        margin: 10px 0px 10px 0px !important;
    }
	
	.thwma-cart-modal-close2{
		margin-top: 12px !important
	}

	.rtl .afb-choice__item{
		text-align: right !important
	}
    .ui-dialog.ui-widget {
        z-index: 11111119999 !important;
    }

    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    body {
        font-family: 'Sweet Sans On Air', sans-serif;
/*         background-color: #f5f5f5; */
/*         padding: 40px 20px; */
/*         letter-spacing: 1px; */
    }

	
	.afb-panel__sheet button:focus{
		background: transparent !important;
		color: black
	}
	
	.step_choice{
		height: 100%;
		display: flex;
		flex-direction: column;
	}
	
	.afb-qty-btn:focus{
		background: transparent !important;
		color: black !important
	}
	
	.step_choice .afb-choice{
		flex: 1;
		display: flex;
		flex-direction: column;
		justify-content: center;
		margin-top: -40px;
	}
	
	
    .form-container {
        max-width: 1200px;
        margin: 0 auto;
        display: flex;
        gap: 40px;
        flex-wrap: wrap;
    }

    .form-box {
        flex: 1;
        min-width: 300px;
        background: transparent;
        border: 1px solid #232323;
        padding: 24px;
        position: relative;
    }

    .form-title {
        text-align: center;
        margin-bottom: 25px;
        font-weight: 700;
        text-transform: uppercase;
        font-size: 12px;
        letter-spacing: 2px;
        color: #232323;
    }

    .form-group {
        margin-bottom: 18px;
    }

    .form-label {
        display: block;
        text-transform: uppercase;
        font-size: 9px;
        letter-spacing: 2px;
        margin-bottom: 0px;
        font-weight: 500;
        color: #232323;
		margin-bottom: -2px
    }

    .required {
        color: red;
    }

    .input-wrapper {
        position: relative;
        display: flex;
        align-items: center;
    }

    .form-input {
        width: 100%;
        padding-bottom: 0px !important;
		padding-top: 0px !important;
		padding-right: 0px ;
		padding-left: 0px ;
        border: none;
        border-bottom: 1px solid #1d1d1b;
        background: transparent;
        font-size: 12px;
        letter-spacing: 2px;
        color: #1d1d1b;
        outline: none;
        border: 0px !important;
        border-bottom: 1px solid #1d1d1b !important;
        text-indent: 0 ;
        padding-top: 0px !important;
		border-radius: 0px !important;
    }

	.form-input:not(#user_phone):not(#billing_phone):not(#shipping_phone):not([type="tel"]){
		padding-right: 0px !important;
		padding-left: 0px !important;
	}
	
    .form-input:focus {
        border-bottom-color: #232323;
    }

    .password-toggle {
        background: #000;
        color: white;
        border: none;
        padding: 8px 12px;
        font-size: 8px;
        text-transform: uppercase;
        letter-spacing: 1.5px;
        cursor: pointer;
        margin-left: 10px;
        font-weight: 500;
    }

    .password-toggle:hover {
        background: #333;
    }

    .form-submit {
        width: 100%;
        background: #1d1d1b;
        color: white;
        border: 1px solid #000;
        padding: 14px 0;
        font-size: 10px;
        font-weight: 500;
        text-transform: uppercase;
        letter-spacing: 2px;
        cursor: pointer;
        margin-top: 2px;
    }

    .form-submit:hover {
        background: #000;
    }

    .form-link {
        text-align: center;
        margin-top: 20px;
    }

    .form-link a {
        color: #929396;
        text-decoration: none;
        font-size: 9px;
        letter-spacing: 2px;
    }

    .form-link a:hover {
        color: #232323;
    }

    .country-select {
        width: 100%;
        padding: 8px 0;
        border: none;
        border-bottom: 1px solid #1d1d1b;
        background: transparent;
        font-size: 12px;
        letter-spacing: 2px;
        color: #1d1d1b;
        outline: none;
        cursor: pointer;
    }

    .address-input {
        border: 1px solid #ddd;
        padding: 8px 12px;
        width: 100%;
        font-size: 12px;
        letter-spacing: 1px;
        color: #1d1d1b;
    }

    .form-message {
        padding: 10px;
        margin-bottom: 15px;
        border: 1px solid;
        font-size: 11px;
        letter-spacing: 1px;
        text-align: center;
    }

    .form-message.success {
        background-color: #d4edda;
        border-color: #c3e6cb;
        color: #155724;
    }

    .form-message.error {
        background-color: #f8d7da;
        border-color: #f5c6cb;
        color: #721c24;
    }

    @media (max-width: 768px) {
        .form-container {
            flex-direction: column;
            gap: 20px;
        }

        .form-box {
            min-width: auto;
            padding: 15px;
        }

        /* Prevent iOS Safari auto-zoom on input focus by ensuring 16px minimum font-size */
        .form-input,
        .country-select,
        .address-input,
        input[type="text"],
        input[type="email"],
        input[type="password"],
        input[type="number"],
        input[type="search"],
        input[type="tel"],
        select,
        textarea {
            font-size: 14px !important;
        }
    }



    .afb-checkout-form,
    .afb-payment-form {
        padding: 10px;
    }

    .afb-section-title {
        text-transform: uppercase;
        font-size: 14px;
        letter-spacing: 2px;
        margin-bottom: 30px;
        color: #232323;
        font-weight: 700;
    }

    .afb-subtitle {
        text-transform: uppercase;
        font-size: 12px;
        letter-spacing: 2px;
        margin-bottom: 20px;
        color: #232323;
        font-weight: 500;
    }

    .afb-checkout-grid {
        display: grid;
/*         grid-template-columns: 30% 68%; */ 
		grid-template-columns: 33% 63%;
        /* 40% first column, 60% second column */
        gap: 40px;
        align-items: start;
    }

    .afb-checkout-column {
        display: flex;
        flex-direction: column;
    }

    .afb-shipping-toggle {
        margin-bottom: 6px;
    }

    .afb-shipping-toggle label {
        display: flex;
        align-items: center;
        cursor: pointer;
        font-size: 11px;
        letter-spacing: 1.5px;
    }

    .afb-shipping-toggle input {
        margin-right: 10px;
    }



    .afb-order-table {
        width: 100%;
        border-collapse: collapse;
    }

    .afb-order-table th,
    .afb-order-table td {
        padding: 12px 0;
        text-align: left;
        border-bottom: 1px solid #e0e0e0;
        font-size: 11px;
        letter-spacing: 1.5px;
    }

    .afb-order-table th {
        font-weight: 500;
        text-transform: uppercase;
    }

    .afb-quantity {
        display: flex;
        align-items: center;
    }
	
	
	.afb-quantity button:hover,
	.afb-quantity button:focus{
		color: black !important
	}
	
	
	.thwma-thslider-viewport.multi-shipping{
		overflow-x: auto !important;
	}

	.afb-order-item .afb-cart-remove{
		margin-left: 0px !important;
	}

    .afb-quantity-input {
        width: 40px;
        text-align: center;
        margin: 0 5px;
        border: 1px solid #ddd;
        padding: 5px;
    }

    .afb-checkout-actions {
        display: flex;
        justify-content: space-between;
        margin-top: 40px;
    }

    .afb-back-button,
    .afb-next-button,
    .afb-submit-button {
        padding: 14px 30px;
        text-transform: uppercase;
        letter-spacing: 2px;
        font-size: 10px;
        font-weight: 500;
        cursor: pointer;
    }

    .afb-back-button {
        background: transparent;
        border: 1px solid #232323;
        color: #232323;
    }

    .afb-next-button,
    .afb-submit-button {
        background: #232323;
        border: 1px solid #232323;
        color: white;
    }

    .afb-payment-methods {
        margin-bottom: 30px;
    }

    .afb-payment-method {
        margin-bottom: 15px;
    }

    .afb-payment-method input {
        margin-right: 10px;
    }

    .afb-payment-box {
        padding: 15px;
        margin-top: 10px;
        background: #f9f9f9;
        border: 1px solid #e0e0e0;
    }

    .afb-terms {
        margin: 30px 0;
        font-size: 11px;
        letter-spacing: 1.5px;
    }

    .afb-terms input {
        margin-right: 10px;
    }
	
/* 	.multi-shipping-table .pdct-qty{
		pointer-events: none !important
	} */
	
	
	.shipping_address:not(.shipping_address > div *){
		text-transform: uppercase !important;
		font-family: 'Sweet Sans On Air', sans-serif !important;
		font-size: 10px !important;
		letter-spacing: 2px !important;
		margin-bottom: 6px !important;
		font-weight: 500 !important;
		line-height: 11px !important;
		color: #232323 !important;
	}
	
	
	td.wmap-img-tr {
		width: 25% !important;
	}

	.checkout-thumbnail-img a{
		font-family: 'Sweet Sans On Air', sans-serif;
		letter-spacing: 2px;
		font-size: 10px;
		font-weight: 500;
		text-transform: uppercase;
		color: #232323;
		margin-top: 10px !important;
		display: inline-block;
	}
	
	
	.afb-mobile-title{
		font-family: 'Sweet Sans On Air', sans-serif !important;
		letter-spacing: 2px !important;
		font-size: 10px !important;
		font-weight: 500 !important;
		text-transform: uppercase;
		color: #232323 !important;
		margin-top: 5px !important;
		display: inline-block;
	}
	
	.afb-mobile-title{
		display: none 
	}
	
	#place_order.processing{
		background:gray !important;
		pointer-events: none !important
	}
	
	
	.thwma-adr-text.address-text,
	.thwma-thslider-viewport .complete-aaddress {
		min-height: 100px !important;
	}
	
	.thwma-adr-footer.address-footer{
		padding: 0px 5px !important;
		font-size:10px !important
	}
 

    @media (max-width: 768px) {
		
		.afb-mobile-title{
			display: inline-block;
		}
		.afb-mobile-title a{
			font-family: 'Sweet Sans On Air', sans-serif !important;
			letter-spacing: 2px !important;
			font-size: 10px !important;
			font-weight: 500 !important;
			text-transform: uppercase;
			color: #232323 !important;
			margin-top: 5px !important;
			display: inline-block;
		}
		
		
		.step_choice .afb-choice{
			margin-top: -20px
		}
		
        .afb-checkout-grid {
            grid-template-columns: 1fr;
        }
		
		nav.afb-steps{
			height:auto !important
		}
		nav.afb-steps ol{
			gap:10px !important;
			justify-content: space-around !important;
		}
		
		.checkout{
			padding: 2px !important
		}
		
		.afb-order-review{
			max-width: 100vw !important
		}
		
/* 		.afb-order-col-qty,
		.afb-order-item-qty, */
		.afb-order-item-name{
			/* display: none !important */
		}
		
		.afb-next-button{
			padding: 10px 12px !important
		}
		
		
					.afb-new-close{
						padding: 0px !important
					}
		
		
		.thwma-cart-modal-content{
			width: 85% !important
		}
		
		.thwma-cart-modal-content .thwma-add-adr{
			max-width:90% !important;
			padding: 10px 20px !important;
		}


        .ship_to_diff_adr{
            display: none !important;
        }


        .woocommerce-columns--addresses.col2-set{
            display: flex !important;
        }

        tr:has(.order-actions--heading) {
            display: none !important;
        }
    }
</style>




<script>
    jQuery(document).ready(function ($) {
        // Handle shipping address toggle
        // Handle shipping address toggle
        $('#ship-to-different-address').change(function () {
            if ($(this).is(':checked')) {
                $('#shipping-address-fields').removeClass('is-hidden');

                // Trigger WooCommerce events
                $(document.body).trigger('shipping-address-toggled', [true]);
//                 $(document.body).trigger('update_checkout');
            } else {
                $('#shipping-address-fields').addClass('is-hidden');

                // Trigger WooCommerce events
                $(document.body).trigger('shipping-address-toggled', [false]);
//                 $(document.body).trigger('update_checkout');
            }
        });

        // Initialize on page load
        if ($('#ship-to-different-address').is(':checked')) {
            $('#shipping-address-fields').removeClass('is-hidden');
        }

        // Listen for updates from other plugins
        $(document.body).on('updated_checkout', function () {
            // Re-check the shipping address visibility
            if ($('#ship-to-different-address').is(':checked') && $('#shipping-address-fields').hasClass('is-hidden')) {
                $('#shipping-address-fields').removeClass('is-hidden');
            }
        });



       

        // Handle payment method selection
        $('input[name="payment_method"]').change(function () {
            $('.afb-payment-box').hide();
            $('.payment_method_' + $(this).val()).show();
        }).first().trigger('change');

		
		
		
		function showWcErrors(errors) {
			var $errorList = $('<ul>', {
				class: 'woocommerce-error',
				role: 'alert'
			});

			errors.forEach(function(error) {
				$errorList.append($('<li>').text(error));
			});

			$('.woocommerce-error, .woocommerce-message').remove(); // Remove any existing messages
			$('.afb-wc-errors').html($errorList);

			// Scroll to errors
			$('html, body').animate({
				scrollTop: $('.afb-wc-errors').offset().top - 100
			}, 500);
		}

		
		// Handle form submission
jQuery(function($) {
    // Ensure multiship selects are normalized on load and when multiship is enabled
    var $checkoutForm = $('form.woocommerce-checkout');
    function afbNormalizeThwmaShippingSelects($targetForm) {
        if (!$targetForm || !$targetForm.length) { return; }
        $targetForm.find('select[name^="thwma-shipping-alt"]').each(function() {
            var $sel = $(this);
            var rawName = $sel.attr('name') || '';
            var match = rawName.match(/^thwma-shipping-alt\[(\d+)\]\[([a-f0-9]{32})\]$/i);
            var prodID = match ? match[1] : ($sel.attr('data-product_id') || $sel.attr('data-product-id') || $sel.data('product_id') || $sel.data('product-id') || '');
            var cartKey = match ? match[2] : ($sel.attr('data-cart_key') || $sel.attr('data-cart-key') || $sel.data('cart_key') || $sel.data('cart-key') || '');
            if (!prodID || !cartKey) { return; }
            prodID = String(prodID);
            cartKey = String(cartKey);
            $sel.attr('data-product_id', prodID);
            $sel.attr('data-product-id', prodID);
            $sel.attr('data-cart_key', cartKey);
            $sel.attr('data-cart-key', cartKey);
            $sel.attr('name', 'thwma-shipping-alt[' + prodID + '][' + cartKey + ']');
        });
    }
    // Initial run and event hooks
    afbNormalizeThwmaShippingSelects($checkoutForm);
    $(document).on('afb:multishipEnabled', function() {
        afbNormalizeThwmaShippingSelects($checkoutForm);
    });
    $(document).on('updated_checkout wc_fragments_refreshed', function() {
        afbNormalizeThwmaShippingSelects($checkoutForm);
    });

    // Normalize whenever any select in .multi-shipping-table changes (delegated for dynamic elements)
    $(document).on('change', '.multi-shipping-table select', function () {
       
        afbNormalizeThwmaShippingSelects($checkoutForm);
    });

    $('#place_order.afb-submit-button').on('click', function(e) {
        e.preventDefault();

        var $form = $('form.woocommerce-checkout');

        if (!$form.length) {
            alert('<?php echo esc_js(__('Le formulaire de commande est introuvable.', 'afb-offcanvas')); ?>');
            return false;
        }

        // Check if terms are accepted
        if (!$('#afb_terms').is(':checked')) {
			let afb_terms = $('#afb_terms') 
           
			if(afb_terms && !afb_terms.is(':checked')){
				document.querySelector("#terms_alert").style.display = "block"
				e.preventDefault()
				return;
			}
			else{
				document.querySelector("#terms_alert").style.display = "none"
			}
			
			alert('<?php echo esc_js(__('Veuillez accepter les conditions générales.', 'afb-offcanvas')); ?>');
            return false;
        }

        // Check if a payment method is selected
        var paymentMethod = $('input[name="payment_method"]:checked').val();
        if (!paymentMethod) {
            alert('<?php echo esc_js(__('Veuillez sélectionner un mode de paiement.', 'afb-offcanvas')); ?>');
            return false;
        }
		
		
		
		//validationn
		// 3. Validate required fields manually
		var requiredFields = [
			{ name: 'billing_first_name', label: '<?php echo esc_js(__('Prénom', 'afb-offcanvas')); ?>' },
			{ name: 'billing_last_name', label: '<?php echo esc_js(__('Nom', 'afb-offcanvas')); ?>' },
			{ name: 'billing_email', label: '<?php echo esc_js(__('Adresse e-mail', 'afb-offcanvas')); ?>' },
			{ name: 'billing_address_1', label: '<?php echo esc_js(__('Adresse', 'afb-offcanvas')); ?>' },
			{ name: 'billing_city', label: '<?php echo esc_js(__('Ville', 'afb-offcanvas')); ?>' },
			// Postcode should NEVER be mandatory
			// { name: 'billing_postcode', label: 'Code postal' },
			{ name: 'billing_country', label: '<?php echo esc_js(__('Pays', 'afb-offcanvas')); ?>' },
			{ name: 'billing_phone', label: '<?php echo esc_js(__('Téléphone', 'afb-offcanvas')); ?>' }
		];

		// Explicit alert if billing phone is missing
		var $billingPhone = $('[name="billing_phone"]');
		if (!$billingPhone.length || !$billingPhone.val().trim()) {
			alert('<?php echo esc_js(__('Veuillez saisir votre numéro de téléphone de facturation.', 'afb-offcanvas')); ?>');
			$('#place_order.afb-submit-button').prop('disabled', false).removeClass('processing').html("<?php echo esc_js(__('Payer maintenant', 'afb-offcanvas')); ?>");
			return false;
		}

		var missingFields = [];

		requiredFields.forEach(function(field) {
			var $input = $('[name="' + field.name + '"]');

			if (!$input.length || !$input.val().trim()) {
				missingFields.push(field.label);
				$input.addClass('field-error'); // Optional: add error class for styling
			} else {
				$input.removeClass('field-error');
			}
		});

		if (missingFields.length > 0) {
			var message = '<?php echo esc_js(__('Veuillez remplir les champs obligatoires suivants : ', 'afb-offcanvas')); ?>' + missingFields.join(', ');
			showWcErrors([message]);
			$('#place_order.afb-submit-button').prop('disabled', false).removeClass('processing').html("<?php echo esc_js(__('Payer maintenant', 'afb-offcanvas')); ?>");
			return false;
		}

		
		
		


		
		
		
		

// multi_shipping_adr_data
// {"3d2fc225ebb8d4fc19d53fc9097cb9e1":{"product_id":"5893","address_name":""},"6b8d6a1f7c3404afdb2db4ab07e0ea02":{"product_id":"5893","address_name":"address_3"}}
  

// Show loading
        $('#place_order.afb-submit-button').prop('disabled', true).addClass('processing').html("<?php echo esc_js(__('traitement...', 'afb-offcanvas')); ?>");

        
        // Copy custom inputs from afb forms into the main checkout form
        $('.afb-checkout-form, .afb-payment-form').find('input, select, textarea').each(function() {
            var name = $(this).attr('name');
            var value = $(this).val();

			console.log($(this), `${name}: ${value}`);
			
            if (name) {
                // Check if the input already exists in the main form
                var $existing = $form.find('[name="' + name + '"]');
                if ($existing.length) {


                    $existing.val(value);
                } else {
                    $('<input>', {
                        type: 'hidden',
                        name: name,
                        value: value
                    }).appendTo($form);
                }
            }
        });




        
        // Normalize thwma-shipping-alt selects and build multi_shipping_adr_data
        var formDataToSend = (function() {
            var multiShippingData = {};
            // Find all shipping select fields
            $form.find('select[name^="thwma-shipping-alt"]').each(function() {
                var $sel = $(this);
                var name = $sel.attr('name') || '';
                // Try to parse prodID/cartKey from name
                var nameMatch = name.match(/^thwma-shipping-alt\[(\d+)\]\[([a-f0-9]{32})\]$/i);
                var prodID = nameMatch ? nameMatch[1] : ($sel.attr('data-product_id') || $sel.attr('data-product-id') || '');
                var cartKey = nameMatch ? nameMatch[2] : ($sel.attr('data-cart_key') || $sel.attr('data-cart-key') || '');

                if (!prodID || !cartKey) {
                    console.warn('Shipping select missing identifiers:', $sel.get(0));
                    return; // skip if identifiers not available
                }

                // Ensure dataset attributes exist (both styles) and normalize name
                $sel.attr('data-product_id', prodID);
                $sel.attr('data-cart_key', cartKey);
                $sel.attr('data-product-id', prodID);
                $sel.attr('data-cart-key', cartKey);
                $sel.attr('name', `thwma-shipping-alt[${prodID}][${cartKey}]`);

                // Collect data for JSON payload
                multiShippingData[cartKey] = {
                    product_id: String(prodID),
                    address_name: String($sel.val() || '')
                };
            });

            // Serialize after normalization
            var arr = $form.serializeArray();
            // Remove any flat thwma-shipping-alt entries
            arr = arr.filter(function(item) { return item.name !== 'thwma-shipping-alt'; });
            // Ensure only one multi_shipping_adr_data entry (remove any hidden field duplicates)
            arr = arr.filter(function(item) { return item.name !== 'multi_shipping_adr_data'; });
            // Inject multi_shipping_adr_data JSON
            arr.push({ name: 'multi_shipping_adr_data', value: JSON.stringify(multiShippingData) });
            return arr;
        })();

        // Submit via WooCommerce native AJAX
        $.ajax({
            type: 'POST',
            url: wc_checkout_params.checkout_url,
            data: formDataToSend,
            dataType: 'json',
            success: function(response) {
                $('#place_order.afb-submit-button').prop('disabled', false).removeClass('processing').html("<?php echo esc_js(__('Payer maintenant', 'afb-offcanvas')); ?>");

                if (response.result === 'success') {
                    window.location.href = response.redirect;
                } else if (response.result === 'failure') {
                    if (response.messages) {
                        $('.woocommerce-error, .woocommerce-message').remove();
                        $('.afb-wc-errors').html(response.messages);
                        $('html, body').animate({
                            scrollTop: $('.afb-wc-errors').offset().top - 100
                        }, 500);
                    } else {
                        alert('<?php echo esc_js(__('Une erreur est survenue. Veuillez réessayer.', 'afb-offcanvas')); ?>');
                    }
                }
            },
            error: function(xhr, status, error) {
                $('#place_order.afb-submit-button').prop('disabled', false).removeClass('processing').html("<?php echo esc_js(__('Payer maintenant', 'afb-offcanvas')); ?>");
                console.error('AJAX Error:', status, error);
                alert('<?php echo esc_js(__('Erreur de connexion. Veuillez vérifier votre réseau et réessayer.', 'afb-offcanvas')); ?>');
            }
        });
    });
});


		
		
       
    });




    //hide multi product address
    jQuery(document).ready(function ($) {
        // Handle initial state
        function toggleMultiShippingWrapper() {
            var $checkbox = $('input[name="ship_to_multi_address"]');
            var $wrapper = $checkbox.siblings('.multi-shipping-wrapper,.thwma_cart_shipping_button');

            if ($checkbox.is(':checked')) {
                $wrapper.show();
                $("#shipping-address-fields > .form-group").hide()
                // Ensure multi-shipping fields are added when initially checked
                if (typeof handleMultiShippingFields === 'function') {
                    handleMultiShippingFields();
                }
            } else {
                $wrapper.hide();
                $("#shipping-address-fields > .form-group").show()
            }
        }

        // Run on page load
        toggleMultiShippingWrapper();

        // Handle checkbox change events
        $(document).on('change', 'input[name="ship_to_multi_address"]', function () {
            toggleMultiShippingWrapper();
            // Call handler whenever the input is checked
            if ($(this).is(':checked') && typeof handleMultiShippingFields === 'function') {
                handleMultiShippingFields();
            }
        });

        // Handle WooCommerce AJAX updates if needed
        $(document.body).on('updated_checkout', function () {
            toggleMultiShippingWrapper();
            var $checkbox = $('input[name="ship_to_multi_address"]');
            if ($checkbox.is(':checked') && typeof handleMultiShippingFields === 'function') {
                handleMultiShippingFields();
            }
        });
    });
</script>













<!-- // Add message fields -->
<script>

    jQuery(document).ready(function ($) {
        // Add message fields when multi-address is checked
        function handleMultiShippingFields() {
            if ($('input[name="ship_to_multi_address"]').is(':checked')) {
                addMessageFields();
            }
        }

        // Initial check
        handleMultiShippingFields();

        // Handle checkbox change
        $(document).on('change', 'input[name="ship_to_multi_address"]', handleMultiShippingFields);

        // Handle WooCommerce AJAX updates
        $(document.body).on('updated_checkout updated_cart_totals', handleMultiShippingFields);

        // lightweight client-side cache for multi-shipping field values
        window.AFB_MS_CACHE = window.AFB_MS_CACHE || {};
        function getMsKey(productId, cartKey){ return String(productId || '') + '|' + String(cartKey || ''); }

        function addMessageFields() {
            $('.multi-shipping-wrapper table tr').each(function () {
                var $row = $(this);
                // Require a shipping options select in the row
                var $select = $row.find('.thwma-cart-shipping-options');
                if ($select.length === 0) {
                    return;
                }

                // Extract product ID and cart key safely
                var selectId = $select.attr('id') || '';
                var parts = selectId.split('_');
                var productId = parts.length > 1 ? parts[1] : '';
                var cartKey   = parts.length > 2 ? parts[2] : '';
                var msKey     = getMsKey(productId, cartKey);
                var cache     = window.AFB_MS_CACHE;

                // Prepare fields
                var needsMessage = $row.find('.product-message-field').length === 0;
                var needsPhone   = $row.find('.product-phone-field').length === 0;

                if (!needsMessage && !needsPhone) {
                    return; // already present
                }

                var $cell = $row.find('td').last();

                if (needsMessage) {
                    var messageField = $('<div class="product-message-field">' +
                        '<label><?php esc_html_e("MESSAGE CADEAU", "afb-offcanvas"); ?></label>' +
                        '<textarea class="product-message" name="product_messages[' + productId + '][' + cartKey + ']" ' +
                        'data-product-id="' + productId + '" data-cart-key="' + cartKey + '" ' +
                        'placeholder="<?php esc_html_e("Votre message pour cet article...", "afb-offcanvas"); ?>"></textarea>' +
                        '</div>');
                    // hydrate from cache if available
                    if (cache[msKey] && typeof cache[msKey].message !== 'undefined') {
                        messageField.find('textarea.product-message').val(cache[msKey].message);
                    }
                    $cell.append(messageField);
                }

                if (needsPhone) {
                    var phoneField = $('<div class="product-phone-field">' +
                        '<label><?php esc_html_e("NUMÉRO DE TÉLÉPHONE", "afb-offcanvas"); ?></label>' +
                        '<input type="tel" class="product-phone afb-phones" name="product_phones[' + productId + '][' + cartKey + ']" ' +
                        'data-product-id="' + productId + '" data-cart-key="' + cartKey + '" ' +
                        'placeholder="<?php esc_html_e("Numéro de téléphone pour cet article...", "afb-offcanvas"); ?>" />' +
                        '</div>');
                    // hydrate from cache if available
                    if (cache[msKey] && typeof cache[msKey].phone !== 'undefined') {
                        phoneField.find('input.product-phone').val(cache[msKey].phone);
                    }
                    $cell.append(phoneField);
                }
            });
        }

        // Persist user input into cache on-the-fly
        $(document).on('input change', '.multi-shipping-wrapper .product-message, .multi-shipping-wrapper .product-phone', function(){
            var $el = $(this);
            var productId = $el.data('product-id') || '';
            var cartKey   = $el.data('cart-key') || '';
            var msKey     = getMsKey(productId, cartKey);
            var cache     = window.AFB_MS_CACHE || (window.AFB_MS_CACHE = {});
            if (!cache[msKey]) { cache[msKey] = {}; }
            if ($el.hasClass('product-message')) {
                cache[msKey].message = $el.val();
            } else {
                cache[msKey].phone = $el.val();
            }
        });

        // Snapshot values just before WooCommerce AJAX refreshes to guarantee capture
        function snapshotMultiShippingValues(){
            jQuery('.multi-shipping-wrapper').find('.product-message, .product-phone').each(function(){
                var $el = jQuery(this);
                var productId = $el.data('product-id') || '';
                var cartKey   = $el.data('cart-key') || '';
                var msKey     = getMsKey(productId, cartKey);
                var cache     = window.AFB_MS_CACHE || (window.AFB_MS_CACHE = {});
                if (!cache[msKey]) { cache[msKey] = {}; }
                if ($el.hasClass('product-message')) {
                    cache[msKey].message = $el.val();
                } else {
                    cache[msKey].phone = $el.val();
                }
            });
        }
        $(document).ajaxSend(function(event, xhr, settings){
            try {
                if (settings && settings.url && settings.url.indexOf('wc-ajax') !== -1) {
                    snapshotMultiShippingValues();
                }
            } catch(e) {}
        });

        // MutationObserver to re-inject fields when DOM is refreshed
        function startMultiShippingObserver() {
            var container = document.querySelector('.multi-shipping-wrapper');
            if (!container) { return; }

            // Disconnect previous observer if any
            if (window.AFB_MS_OBSERVER) {
                try { window.AFB_MS_OBSERVER.disconnect(); } catch(e) {}
            }

            // Simple debounce to avoid thrashing
            function debounce(fn, delay) {
                var t; return function() { clearTimeout(t); t = setTimeout(fn, delay); };
            }
            var debounced = debounce(function(){
                // Ensure fields exist after mutations
                jQuery(function($){ addMessageFields(); });
            }, 150);

            var obs = new MutationObserver(function(mutations){ debounced(); });
            obs.observe(container, { childList: true, subtree: true });
            window.AFB_MS_OBSERVER = obs;
        }

        // Start observer on load and on common WC refresh events
        startMultiShippingObserver();
        $(document.body).on('updated_checkout updated_cart_totals wc_fragments_refreshed', function(){ startMultiShippingObserver(); });
        // Also restart observer after WooCommerce AJAX completes to handle full container replacements
        $(document).ajaxComplete(function(event, xhr, settings){
            try {
                if (settings && settings.url && settings.url.indexOf('wc-ajax') !== -1) {
                    startMultiShippingObserver();
                }
            } catch(e) {}
        });
    });


</script>












<!-- /// Update quantity -->
<script>
 jQuery(function ($) {

    // Add this function to refresh your custom order review
    function refreshCustomOrderReview() {
        $.ajax({
            url: window.location.href,
            type: 'GET',
            data: {
                'get_order_review_fragment': true
            },
            success: function (response) {
                var $newContent = $(response).find('.afb-order-review');
                if ($newContent.length) {
                    $('.afb-order-review').replaceWith($newContent);
                    initQuantityHandlers(); // Reinitialize handlers
                }
            }
        });
    }

    // Function to reinitialize quantity handlers
    function initQuantityHandlers() {
        // Handle quantity changes
        $('.afb-quantity-plus').click(function () {
            var $input = $(this).siblings('.afb-quantity-input');
            var currentVal = parseInt($input.val());
			console.log("curr val", currentVal)
            var cart_key = $input.data('cart-key') || $input.closest('tr').data('key');

            $.ajax({
                type: 'POST',
                url: AFB_AJAX.url, // Use your custom AJAX endpoint
                data: {
                    action: 'afb_cart_update_qty',
                    cart_key: cart_key,
                    delta: 1, // Positive delta for increase
                    nonce: AFB_AJAX.nonce
                },
                beforeSend: function () {
                    $input.prop('disabled', true);
                },
                success: function (response) {
                    if (response.success) {
                        $input.val(currentVal + 1);
                        $(document.body).trigger('updated_cart');

						console.log("updated quan", $input)
                        // Add this to refresh your custom order review
                        refreshCustomOrderReview();
                    } else {
                        // Revert if failed
                        $input.val(currentVal);
                        console.error('Update failed:', response.data);
                    }
                },
                error: function (xhr) {
                    $input.val(currentVal);
                    console.error('AJAX error:', xhr.responseText);
                },
                complete: function () {
                    $input.prop('disabled', false);
                }
            });
        });

        $('.afb-quantity-minus').click(function () {
            var $input = $(this).siblings('.afb-quantity-input');
            var currentVal = parseInt($input.val());
            if (currentVal <= 1) return;

            var cart_key = $input.data('cart-key') || $input.closest('tr').data('key');

            $.ajax({
                type: 'POST',
                url: AFB_AJAX.url,
                data: {
                    action: 'afb_cart_update_qty',
                    cart_key: cart_key,
                    delta: -1, // Negative delta for decrease
                    nonce: AFB_AJAX.nonce
                },
                beforeSend: function () {
                    $input.prop('disabled', true);
                },
                success: function (response) {
                    if (response.success) {
                        $input.val(currentVal -1 );
                        $(document.body).trigger('updated_cart');
console.log("updated quan", $input)
                        // Add this to refresh your custom order review
                        refreshCustomOrderReview();
                    } else {
                        // Revert if failed
                        $input.val(currentVal);
                        console.error('Update failed:', response.data);
                    }
                },
                error: function (xhr) {
                    $input.val(currentVal);
                    console.error('AJAX error:', xhr.responseText);
                },
                complete: function () {
                    $input.prop('disabled', false);
                }
            });
        });

        $('.afb-quantity-input').change(function () {
            var $input = $(this);
            var new_qty = parseInt($input.val());
            var old_qty = parseInt($input.attr('value') || $input.data('old-value') || new_qty);
            var cart_key = $input.data('cart-key') || $input.closest('tr').data('key');

            // Don't process if quantity didn't change
            if (new_qty === old_qty) return;

            // Don't allow quantities less than 1
            if (new_qty < 1) {
                $input.val(old_qty);
                return;
            }

            var delta = new_qty - old_qty;

            $.ajax({
                type: 'POST',
                url: AFB_AJAX.url,
                data: {
                    action: 'afb_cart_update_qty',
                    cart_key: cart_key,
                    delta: delta,
                    nonce: AFB_AJAX.nonce
                },
                beforeSend: function () {
                    $input.prop('disabled', true);
                },
                success: function (response) {
                    if (response.success) {
                        $input.val(currentVal + delta);
                        $(document.body).trigger('updated_cart');

                        // Add this to refresh your custom order review
                        refreshCustomOrderReview();
                    } else {
                        // Revert if failed
                        $input.val(currentVal);
                        console.error('Update failed:', response.data);
                    }
                },
                error: function (xhr) {
                    $input.val(old_qty);
                    console.error('AJAX error:', xhr.responseText);
                },
                complete: function () {
                    $input.prop('disabled', false);
                }
            });
        });
    }

    // Initialize on page load
    jQuery(document).ready(function ($) {
        initQuantityHandlers();
    });
})
</script>





<script>
jQuery(document).ready(function($) {
    // Event delegation for dynamically added elements
    $(document).on('click', '#cart_shipping_form_wrap', function(e) {
		 e.preventDefault();
        e.stopPropagation(); 
		$(this).find(".select2").remove()
        // Destroy Select2 on the clicked element
//         $(this).parent().find("select").select2('destroy');
		 $(this).find("select").removeClass('select2-hidden-accessible')
        
        // Optional: Prevent the default Select2 dropdown from opening
        e.preventDefault();
        e.stopPropagation();
    });

    // Handle WooCommerce AJAX updates if needed
    $(document.body).on('updated_checkout', function() {
        $('#cart_shipping_form_wrap select').each(function() {
            if ($(this).hasClass('select2-hidden-accessible')) {
//                 $(this).select2('destroy');
				 $(this).removeClass('select2-hidden-accessible') 
            }
        });
    });
});
	
	
const removeSelect2_ = ()=>{
	console.log("added")
	
	jQuery("#cart_shipping_form_wrap select").removeClass('select2-hidden-accessible')
	jQuery('#cart_shipping_form_wrap .select2').remove()
}



function waitForCartShippingForm() {
    const targetNode = document.body;
    if (!targetNode) return;

    const observer = new MutationObserver((mutations, obs) => {
        const el = document.querySelector("#cart_shipping_form_wrap");
        if (el) {
            if (typeof removeSelect2_ === "function") {
                removeSelect2_();
            }
            obs.disconnect();  
        }
    });

    observer.observe(targetNode, {
        childList: true,
        subtree: true
    });
}

 
document.addEventListener("DOMContentLoaded", waitForCartShippingForm);

</script>






<!-- //sync billing and shipping fields. -->
<script>
jQuery(document).ready(function($) {
  var fields = [
    'first_name',
    'last_name',
    'company',
    'address_1',
    'address_2',
    'city',
    'state',
    'postcode',
    'country',
    'phone',
    'email'
  ];

  $(document).on('input change', fields.map(f => '#billing_' + f).join(','), function() {
    var id = $(this).attr('id'); // e.g. billing_first_name
    var field = id.replace('billing_', '');
    var $shippingField = $('#shipping_' + field);

    if ($shippingField.length && !$shippingField.val()?.trim()) {
      $shippingField.val($(this).val()).trigger('change');
    }
  });
});


</script>



















<script>
/**
 * Syncs the ThemeHigh Multi-Shipping plugin block with the order details section
 * Matches quantities and removes items that no longer exist in the order
 */
// function syncMultiShippingWithOrderDetails() {
//     // Prevent sync loops
//     if (window.syncInProgress) {
//         console.log('Sync already in progress, skipping...');
//         return;
//     }
    
//     window.syncInProgress = true;
//     console.log('Starting sync between order details and multi-shipping plugin...');
    
//     try {
//         // Get order items from the first block (order details)
//         const orderItems = {};
//         const orderItemElements = document.querySelectorAll('.afb-order-item');
        
//         orderItemElements.forEach(item => {
//             const cartKey = item.getAttribute('data-cart-key');
//             const quantityInput = item.querySelector('.afb-quantity-input');
            
//             if (cartKey && quantityInput) {
//                 const quantity = parseInt(quantityInput.value) || 0;
//                 orderItems[cartKey] = {
//                     quantity: quantity,
//                     element: item
//                 };
//                 console.log(`Order item found: ${cartKey} - Quantity: ${quantity}`);
//             }
//         });
        
//         // Get plugin items from the second block (multi-shipping)
//         const pluginTableRows = document.querySelectorAll('.multi-shipping-table tbody tr.main-pdct-tr');
//         const existingPluginItems = new Set();
        
//         pluginTableRows.forEach(row => {
//             const quantityInput = row.querySelector('.multi-ship-pdct-qty');
            
//             if (quantityInput) {
//                 const cartKey = quantityInput.getAttribute('data-cart_key');
                
//                 if (cartKey) {
//                     existingPluginItems.add(cartKey);
                    
//                     // Check if this item exists in order details
//                     if (orderItems[cartKey]) {
//                         // Item exists - sync quantity
//                         const orderQuantity = orderItems[cartKey].quantity;
//                         const currentPluginQuantity = parseInt(quantityInput.value) || 0;
                        
//                         if (orderQuantity !== currentPluginQuantity) {
//                             console.log(`Syncing quantity for ${cartKey}: ${currentPluginQuantity} -> ${orderQuantity}`);
//                             quantityInput.value = orderQuantity;
                            
//                             // Trigger change event to notify the plugin
//                             quantityInput.dispatchEvent(new Event('change', { bubbles: true }));
//                         }
                        
//                         // Update hidden quantity fields if they exist
//                         const hiddenQtyInputs = row.querySelectorAll('input[name="hiden_qty_key"]');
//                         hiddenQtyInputs.forEach(hiddenInput => {
//                             hiddenInput.value = orderQuantity;
//                         });
                        
//                     } else {
//                         // Item doesn't exist in order details - remove from plugin
//                         console.log(`Removing item ${cartKey} from multi-shipping (not in order details)`);
//                         row.remove();
//                     }
//                 }
//             }
//         });
        
//         // Check for new items that need to be added to the plugin
//         Object.keys(orderItems).forEach(cartKey => {
//             if (!existingPluginItems.has(cartKey)) {
//                 console.log(`New item detected: ${cartKey} - needs to be added to plugin`);
//                 addNewItemToPlugin(cartKey, orderItems[cartKey]);
//             }
//         });
        
//         // Update the hidden multi-shipping data
//         updateMultiShippingData();
        
//         // Trigger plugin refresh events
// //         triggerPluginRefresh();
        
//         console.log('Sync completed');
        
//     } catch (error) {
//         console.error('Error during sync:', error);
//     } finally {
//         // Check if we need a checkout refresh after sync completes
//         if (window.needsCheckoutRefresh) {
//             console.log('Triggering checkout refresh for new items after sync completes');
//             window.needsCheckoutRefresh = false;
            
//             // Trigger refresh after a delay to avoid loops
//             setTimeout(() => {
//                 if (typeof jQuery !== 'undefined') {
// //                     jQuery('body').trigger('update_checkout');
//                 }
//             }, 2000);
//         }
        
//         // Always release the lock
//         setTimeout(() => {
//             window.syncInProgress = false;
//         }, 1000);
//     }
// }


	
	
	

// /**
//  * Updates the hidden multi-shipping address data field
//  */
// function updateMultiShippingData() {
//     const hiddenDataInput = document.querySelector('input.multi-shipping-adr-data');
//     if (!hiddenDataInput) return;
    
//     try {
//         const currentData = JSON.parse(hiddenDataInput.value || '{}');
//         const newData = {};
        
//         // Only keep data for items that still exist in the DOM
//         const existingRows = document.querySelectorAll('.multi-shipping-table tbody tr.main-pdct-tr');
//         existingRows.forEach(row => {
//             const quantityInput = row.querySelector('.multi-ship-pdct-qty');
//             if (quantityInput) {
//                 const cartKey = quantityInput.getAttribute('data-cart_key');
//                 if (cartKey && currentData[cartKey]) {
//                     newData[cartKey] = currentData[cartKey];
//                 }
//             }
//         });
        
//         hiddenDataInput.value = JSON.stringify(newData);
//         console.log('Updated multi-shipping data:', newData);
        
//     } catch (e) {
//         console.error('Error updating multi-shipping data:', e);
//     }
// }

 

// /**
//  * Advanced sync function that also handles visual updates
// //  */
// // function syncMultiShippingAdvanced() {
// //     syncMultiShippingWithOrderDetails();
    
// //     // Additional visual updates
// //     setTimeout(() => {
// //         // Force redraw of select2 elements if they exist
// //         if (typeof jQuery !== 'undefined' && jQuery.fn.select2) {
// //             jQuery('.thwma-cart-shipping-options').each(function() {
// //                 if (jQuery(this).hasClass('select2-hidden-accessible')) {
// //                     jQuery(this).trigger('change.select2');
// //                 }
// //             });
// //         }
        
// //         // Show/hide overlay
// //         const overlay = document.querySelector('.multi-shipping-table-overlay');
// //         if (overlay) {
// //             overlay.style.display = 'block';
// //             setTimeout(() => {
// //                 overlay.style.display = 'none';
// //             }, 500);
// //         }
        
// //     }, 100);
// // }

// // /**
// //  * Set up automatic syncing when order details change
// //  */
// // function setupAutoSync() {
// //     // Prevent multiple setups
// //     if (window.syncSetupComplete) {
// //         return;
// //     }
    
// //     const orderReview = document.getElementById('afb-order-review');
// //     if (!orderReview) {
// //         console.warn('Order review section not found');
// //         return;
// //     }
    
// //     // Flag to prevent loops
// //     window.syncInProgress = false;
    
// //     // Create observer for changes in order details
// //     const observer = new MutationObserver((mutations) => {
// //         // Prevent sync loops
// //         if (window.syncInProgress) {
// //             return;
// //         }
        
// //         let shouldSync = false;
        
// //         mutations.forEach((mutation) => {
// //             // Only sync on specific changes, avoid loops
// //             if (mutation.type === 'childList') {
// //                 // Check if items were added/removed (not just DOM manipulation)
// //                 const addedNodes = Array.from(mutation.addedNodes);
// //                 const removedNodes = Array.from(mutation.removedNodes);
                
// //                 const hasOrderItemChanges = addedNodes.some(node => 
// //                     node.nodeType === 1 && node.classList && node.classList.contains('afb-order-item')
// //                 ) || removedNodes.some(node => 
// //                     node.nodeType === 1 && node.classList && node.classList.contains('afb-order-item')
// //                 );
                
// //                 if (hasOrderItemChanges) {
// //                     shouldSync = true;
// //                 }
// //             }
// //         });
        
// //         if (shouldSync) {
// //             console.log('Order details changed, syncing...');
// //             // Debounce the sync call
// //             clearTimeout(window.syncTimeout);
// //             window.syncTimeout = setTimeout(() => {
// //                 if (!window.syncInProgress) {
// //                     syncMultiShippingAdvanced();
// //                 }
// //             }, 500);
// //         }
// //     });
    
// //     // Start observing with more specific settings
// //     observer.observe(orderReview, {
// //         childList: true,
// //         subtree: true
// //     });
    
// //     // Manual quantity change handler (separate from auto-observer)
// //     document.addEventListener('change', (e) => {
// //         if (e.target.classList.contains('afb-quantity-input') && !window.syncInProgress) {
// //             clearTimeout(window.syncTimeout);
// //             window.syncTimeout = setTimeout(() => {
// //                 if (!window.syncInProgress) {
// //                     syncMultiShippingAdvanced();
// //                 }
// //             }, 500);
// //         }
// //     });
    
// //     window.syncSetupComplete = true;
// //     console.log('Auto-sync setup completed');
// // }

// // // Initialize auto-sync when DOM is ready
// // if (document.readyState === 'loading') {
// //     document.addEventListener('DOMContentLoaded', setupAutoSync);
// // } else {
// //     setupAutoSync();
// // }

// // // Export functions for manual use
// // window.syncMultiShipping = syncMultiShippingWithOrderDetails;
// // window.syncMultiShippingAdvanced = syncMultiShippingAdvanced;
	
	
	

	
	
	
	
/**
 * Syncs the ThemeHigh Multi-Shipping plugin block with the order details section
 * Matches quantities and removes items that no longer exist in the order
 */
function syncMultiShippingWithOrderDetails() {
    // Prevent sync loops
    if (window.syncInProgress) {
        console.log('Sync already in progress, skipping...');
        return;
    }
    
    window.syncInProgress = true;
    console.log('Starting sync between order details and multi-shipping plugin...');
    
    try {
        // Get order items from the first block (order details)
        const orderItems = {};
        const orderItemElements = document.querySelectorAll('#afb-checkout-panel .afb-order-item');
        
        orderItemElements.forEach(item => {
            const cartKey = item.getAttribute('data-cart-key');
            const quantityInput = item.querySelector('.afb-quantity-input');
            
            if (cartKey && quantityInput) {
                const quantity = parseInt(quantityInput.value) || 0;
                orderItems[cartKey] = {
                    quantity: quantity,
                    element: item
                };
                console.log(`Order item found: ${cartKey} - Quantity: ${quantity}`);
            }
        });
		
		
		console.log("order items", orderItems)
        
        // Get plugin items from the second block (multi-shipping)
        const pluginTableRows = document.querySelectorAll('#afb-checkout-panel .multi-shipping-table tbody tr');
        const existingPluginItems = new Set();
        
        pluginTableRows.forEach(row => {
            const quantityInput = row.querySelector('.multi-ship-pdct-qty');
            
            if (quantityInput) {
                const cartKey = quantityInput.getAttribute('data-cart_key');
                
				console.log("cartKey", cartKey)
				
                if (cartKey) {
                    existingPluginItems.add(cartKey);
                    
                    // Check if this item exists in order details
                    if (orderItems[cartKey]) {
						
						console.log("yes it exists see: ", orderItems[cartKey])
						
						console.log("YES IT IS HERE")
                        // Item exists - sync quantity
                        const orderQuantity = orderItems[cartKey].quantity;
                        const currentPluginQuantity = parseInt(quantityInput.value) || 0;
                        
                        if (orderQuantity !== currentPluginQuantity) {
                            console.log(`Syncing quantity for ${cartKey}: ${currentPluginQuantity} -> ${orderQuantity}`);
                            quantityInput.value = orderQuantity;
                            
                            // Trigger change event to notify the plugin
                            quantityInput.dispatchEvent(new Event('change', { bubbles: true }));
                        }
                        
                        // Update hidden quantity fields if they exist
                        const hiddenQtyInputs = row.querySelectorAll('input[name="hiden_qty_key"]');
                        hiddenQtyInputs.forEach(hiddenInput => {
                            hiddenInput.value = orderQuantity;
                        });
                        
                    } else {
                        // Item doesn't exist in order details - remove from plugin
                        console.log(`Removing item ${cartKey} from multi-shipping (not in order details)`);
                        row.remove();
                    }
                }
            }
        });
        
        // Check for new items that need to be added to the plugin
        Object.keys(orderItems).forEach(cartKey => {
            if (!existingPluginItems.has(cartKey)) {
                console.log(`New item detected: ${cartKey} - needs to be added to plugin`);
                addNewItemToPlugin(cartKey, orderItems[cartKey]);
            }
        });
        
        // Update the hidden multi-shipping data
        updateMultiShippingData();
        
        // Trigger plugin refresh events
//         triggerPluginRefresh();
        
        console.log('Sync completed');
        
    } catch (error) {
        console.error('Error during sync:', error);
    } finally {
        // Check if we need a checkout refresh after sync completes
        if (window.needsCheckoutRefresh) {
            console.log('Triggering checkout refresh for new items after sync completes');
            window.needsCheckoutRefresh = false;
            
            // Trigger refresh after a delay to avoid loops
            setTimeout(() => {
                if (typeof jQuery !== 'undefined') {
//                     jQuery('body').trigger('update_checkout');
                }
            }, 2000);
        }
        
        // Always release the lock
        setTimeout(() => {
            window.syncInProgress = false;
        }, 1000);
    }
}
	
	



/**
 * Adds a new item to the plugin's multi-shipping table (string-only, no DOMParser)
 * - Uses jQuery (not $)
 * @param {string} cartKey - the new 32-hex cart key
 * @param {Element} orderItem - <li.afb-order-item> element from Order Details block
 */
function addNewItemToPlugin(cartKey, orderItemObj) {
	const orderItem = orderItemObj.element
	
  try {
    if (!orderItem || !orderItem.classList || !orderItem.classList.contains('afb-order-item')) {
      console.warn('addNewItemToPlugin: invalid orderItem element');
      window.needsCheckoutRefresh = true;
      return;
    }

    // -------- 1) Extract dynamic data from the order item --------
    const prodID = orderItem.getAttribute('data-product-id');
    const imgLinkEl = orderItem.querySelector('.afb-order-item-image a[href]');
    const titleEl1  = orderItem.querySelector('.afb-order-item-name a');
    const titleEl2  = orderItem.querySelector('.afb-mobile-title a');
    const imgEl     = orderItem.querySelector('.afb-order-item-image img');
    const qtyEl     = orderItem.querySelector('.afb-quantity-input');

    const productUrl   = (imgLinkEl && imgLinkEl.getAttribute('href')) || '#';
    const productTitle = ((titleEl1 && titleEl1.textContent) || (titleEl2 && titleEl2.textContent) ||
                         (imgEl && imgEl.getAttribute('alt')) || 'Product').trim();

    const productImg = {
      src:    (imgEl && imgEl.getAttribute('src'))    || '',
      srcset: (imgEl && imgEl.getAttribute('srcset')) || '',
      sizes:  (imgEl && imgEl.getAttribute('sizes'))  || '',
      alt:    (imgEl && imgEl.getAttribute('alt'))    || productTitle,
      width:  (imgEl && imgEl.getAttribute('width'))  || '300',
      height: (imgEl && imgEl.getAttribute('height')) || '300',
    };

    const quantity = Math.max(1, parseInt((qtyEl && qtyEl.value) || '1', 10) || 1);

    // -------- 1.5) Extract addresses from DOM --------
    const addressOptions = extractAddressOptions();

    // -------- 2) Your EXACT template string (unchanged base) --------
    let htmlString = `<tr class="main-pdct-tr"><td class="wmap-img-tr"><div class="checkout-thumbnail-img"><a href="https://damyel.co.il/product/brun-noisettes-2/"><img fetchpriority="high" width="300" height="300" src="https://damyel.co.il/wp-content/uploads/2025/08/tablette-chocolat-orange-6-300x300.png" class="attachment-woocommerce_thumbnail size-woocommerce_thumbnail" alt="BRUN NOISETTES" decoding="async" srcset="https://damyel.co.il/wp-content/uploads/2025/08/tablette-chocolat-orange-6-300x300.png 300w, https://damyel.co.il/wp-content/uploads/2025/08/tablette-chocolat-orange-6-150x150.png 150w, https://damyel.co.il/wp-content/uploads/2025/08/tablette-chocolat-orange-6-100x100.png 100w" sizes="(max-width: 300px) 100vw, 300px"></a><p class="product-thumb-name"><a href="https://damyel.co.il/product/brun-noisettes-2/">BRUN NOISETTES</a></p></div></td><td><input type="number" min="1" name="pdct-qty" value="1" class="multi-ship-pdct-qty pdct-qty main-pdct-qty pdct-qty-39ff31885bd470d3d1006e6257c58ace" data-cart_key="39ff31885bd470d3d1006e6257c58ace"></td><td><input class="multi-ship-item" type="hidden" data-multi_ship_id="multi_ship_1" data-multi_ship_parent_id="0" data-updated_qty="1" data-sub_row_stage="1"><div id="thwma_cart_multi_shipping_display" class="thwma_cart_multi_shipping_display thwma_cart_multi_shipping_display_39ff31885bd470d3d1006e6257c58ace" style="display:block"><input type="hidden" name="hiden_qty_key" class="hiden_qty_key hiden_qty_key_1" value="1" data-field_name="cart[39ff31885bd470d3d1006e6257c58ace][qty]_1" data-qty_hd_key="1"><p class="form-row form-row form-row-wide enhanced_select select2-selection cart_shipping_adr_slct" id="thwma-shipping-alt_field-multi-ship" data-priority=""><span class="woocommerce-input-wrapper"><select name="thwma-shipping-alt[2136][39ff31885bd470d3d1006e6257c58ace]" id="thwma-shipping-alt_2136_39ff31885bd470d3d1006e6257c58ace" class="thwma-cart-shipping-options select " data-allow_clear="true" data-placeholder="House 689, Street 64, G9/4, Islamabad, 04491, Islamabad" data-product_id="2136" data-cart_key="39ff31885bd470d3d1006e6257c58ace" data-exist_multi_adr="" data-key_multi_adr=""><option value="">Sélectionner l'adresse</option><option value="address_1" selected="selected">House 689, Street 64, G9/4, Islamabad, 04491, Islamabad</option></select></span></p></div><input type="hidden" value="1" name="ship_to_diff_hidden" class="ship_to_diff_hidden"><a href="" class="ship_to_diff_adr add-dif-ship-adr link_disabled_class" data-product_id="2136" data-cart_quantity="1" data-variation_id="0" data-cart_item="{&quot;unique_key&quot;:&quot;1ac5d78cf2172224bd7f0420cdd476e1&quot;,&quot;time&quot;:&quot;02:16:22am&quot;,&quot;key&quot;:&quot;39ff31885bd470d3d1006e6257c58ace&quot;,&quot;product_id&quot;:2136,&quot;variation_id&quot;:0,&quot;variation&quot;:[],&quot;quantity&quot;:1,&quot;data_hash&quot;:&quot;b5c1d5ca8bae6d4896cf1807cdf763f0&quot;,&quot;line_tax_data&quot;:{&quot;subtotal&quot;:[],&quot;total&quot;:[]},&quot;line_subtotal&quot;:40,&quot;line_subtotal_tax&quot;:0,&quot;line_total&quot;:40,&quot;line_tax&quot;:0,&quot;multi_ship_address&quot;:{&quot;product_id&quot;:2136,&quot;variation_id&quot;:0,&quot;quantity&quot;:1,&quot;multi_ship_id&quot;:&quot;multi_ship_1&quot;,&quot;multi_ship_parent_id&quot;:0,&quot;child_keys&quot;:[],&quot;parent_cart_key&quot;:&quot;&quot;},&quot;data&quot;:{}}" data-cart_item_key="39ff31885bd470d3d1006e6257c58ace" data-updated_qty="1" style="display:none">Expédier à une adresse différente</a><div class="product-message-field"><label><?= esc_html_e("Gift Message.", "afb-offcanvas") ?></label><textarea class="product-message" name="product_messages[2136][39ff31885bd470d3d1006e6257c58ace]" data-product-id="2136" data-cart-key="39ff31885bd470d3d1006e6257c58ace" placeholder="Votre message pour cet article..."></textarea></div><div class="product-phone-field"><label><?= esc_html_e("NUMÉRO DE TÉLÉPHONE", "afb-offcanvas") ?></label><input type="tel" class="product-phone afb-phones" name="product_phones[2136][39ff31885bd470d3d1006e6257c58ace]" data-product-id="2136" data-cart-key="39ff31885bd470d3d1006e6257c58ace" placeholder="<?= esc_html_e("Numéro de téléphone pour cet article...", "afb-offcanvas") ?>" /></div></td></tr>`;
 
    // -------- 3) Replace product bits (anchors, title, image, visible qty) --------
    htmlString = htmlString
      // image + title anchors (both) to productUrl
      .replace(/(<div class="checkout-thumbnail-img"><a href=")[^"]+(">)/, `$1${productUrl}$2`)
      .replace(/(<p class="product-thumb-name"><a href=")[^"]+(">)/, `$1${productUrl}$2`)
      // title text
      .replace(/(<p class="product-thumb-name"><a href="[^"]*">)[\s\S]*?(<\/a><\/p>)/, `$1${escapeHtml(productTitle)}$2`)
      // image attributes
      .replace(/(<img[^>]*?)\swidth="[^"]*"/, `$1 width="${productImg.width}"`)
      .replace(/(<img[^>]*?)\sheight="[^"]*"/, `$1 height="${productImg.height}"`)
      .replace(/(<img[^>]*?)\ssrc="[^"]*"/, `$1 src="${productImg.src}"`)
      .replace(/(<img[^>]*?)\salt="[^"]*"/, `$1 alt="${escapeHtml(productImg.alt)}"`)
      .replace(/(<img[^>]*?)\ssrcset="[^"]*"/, productImg.srcset ? `$1 srcset="${productImg.srcset}"` : `$1`)
      .replace(/(<img[^>]*?)\ssizes="[^"]*"/,  productImg.sizes  ? `$1 sizes="${productImg.sizes}"`   : `$1`)
      // visible qty input value
      .replace(/(<input[^>]*\bmulti-ship-pdct-qty\b[^>]*\bvalue=")[^"]*"/, `$1${quantity}"`);

    // -------- 3.5) Replace address options in the select --------
    htmlString = replaceAddressOptions(htmlString, addressOptions);

    // -------- 4) Replace EVERY occurrence of the template's old 32-hex key with cartKey --------
    const oldKeyMatch = htmlString.match(/[a-f0-9]{32}/i);
    if (oldKeyMatch) {
      const oldKey = oldKeyMatch[0];
      const reAllKeys = new RegExp(oldKey, 'gi');
      htmlString = htmlString.replace(reAllKeys, cartKey);
    }

    // -------- 5) Fix encoded JSON inside data-cart_item (key, quantity, unique_key, time) --------
    htmlString = htmlString.replace(/data-cart_item="([^"]+)"/, (m, encJson) => {
      const decoded = encJson.replace(/&quot;/g, '"').replace(/&amp;/g, '&');
      let obj; try { obj = JSON.parse(decoded); } catch { obj = {}; }
      obj.key = cartKey;
      obj.quantity = quantity;
      obj.unique_key = genHex32();
      obj.time = currentTimeHMS();
      const reEnc = JSON.stringify(obj).replace(/&/g, '&amp;').replace(/"/g, '&quot;');
      return `data-cart_item="${reEnc}"`;
    });

    // Ensure data-cart_item_key and any data-updated_qty reflect current values
    htmlString = htmlString
      .replace(/data-cart_item_key="[^"]*"/, `data-cart_item_key="${cartKey}"`)
      .replace(/data-updated_qty="[^"]*"/g, `data-updated_qty="${quantity}"`)
      // hidden qty echo value
      .replace(/(name="hiden_qty_key"[^>]*\svalue=")[^"]*"/, `$1${quantity}"`)
      // textarea name ...[<key>]
      .replace(/name="product_messages\[2136]\[[a-f0-9]{32}\]"/i, `name="product_messages[${prodID}][${cartKey}]"`)
      // phone input name ...[<key>] 
      .replace(/name="product_phones\[2136]\[[a-f0-9]{32}\]"/i, `name="product_phones[${prodID}][${cartKey}]"`)
      // shipping address select name
      .replace(/name="thwma-shipping-alt\[2136]\[[a-f0-9]{32}\]"/i, `name="thwma-shipping-alt[${prodID}][${cartKey}]"`)
      // ensure product/cart identifiers on the shipping select (both kebab and underscore)
      .replace(/data-product_id="[^"]*"/i, `data-product_id="${prodID}"`)
      .replace(/data-product-id="[^"]*"/i, `data-product-id="${prodID}"`)
      .replace(/data-cart_key="[^"]*"/i, `data-cart_key="${cartKey}"`)
      .replace(/data-cart-key="[^"]*"/i, `data-cart-key="${cartKey}"`)
      // if underscore attrs are missing, add them to the shipping select
      .replace(/(<select[^>]*\bthwma-cart-shipping-options\b[^>]*)(>)/i, (m, pre, end) => {
        const hasProdUnderscore = /data-product_id="/i.test(pre);
        const hasCartUnderscore = /data-cart_key="/i.test(pre);
        let out = pre;
        if (!hasProdUnderscore) out += ` data-product_id="${prodID}"`;
        if (!hasCartUnderscore) out += ` data-cart_key="${cartKey}"`;
        return out + end;
      });

    // -------- 6) Append to table (using jQuery, not $) --------
    const $tbody = jQuery('.multi-shipping-table tbody');
    if (!$tbody.length) {
      console.warn('multi-shipping table body not found');
      window.needsCheckoutRefresh = true;
      return;
    }
    $tbody.append(htmlString);
    window.needsCheckoutRefresh = true;
  } catch (err) {
    console.error('addNewItemToPlugin failed:', err);
    window.needsCheckoutRefresh = true;
  }

  // ---- helpers ----
  function escapeHtml(s) {
    return String(s).replace(/[&<>"']/g, (c) =>
      ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c])
    );
  }

  function genHex32() {
    // simple 32-hex generator; prefer crypto if available
    if (window.crypto && crypto.getRandomValues) {
      const arr = new Uint8Array(16);
      crypto.getRandomValues(arr);
      return Array.from(arr, b => b.toString(16).padStart(2,'0')).join('');
    }
    const hex = '0123456789abcdef';
    let out = '';
    for (let i = 0; i < 32; i++) out += hex[Math.floor(Math.random()*16)];
    return out;
  }

  function currentTimeHMS() {
    const d = new Date();
    let h = d.getHours(), ampm = h >= 12 ? 'pm' : 'am';
    h = h % 12 || 12;
    const hh = String(h).padStart(2,'0');
    const mm = String(d.getMinutes()).padStart(2,'0');
    const ss = String(d.getSeconds()).padStart(2,'0');
    return `${hh}:${mm}:${ss}${ampm}`;
  }

  /**
   * Extract address options from the DOM
   * @returns {Array} Array of {value, text} objects
   */
  function extractAddressOptions() {
    const addressOptions = [];
    
    try {
      const tileField = document.querySelector('#thwma-cart-shipping-tile-field');
      if (!tileField) {
        console.warn('Address tile field not found');
        return addressOptions;
      }

      const addressList = tileField.querySelector('ul.thwma-thslider-list-ms');
      if (!addressList) {
        console.warn('Address list not found');
        return addressOptions;
      }

      const addressItems = addressList.querySelectorAll('li[value]');
      
      addressItems.forEach(li => {
        const value = li.getAttribute('value');
        const textEl = li.querySelector('.tile-adrr-text');
        let text = textEl ? textEl.innerHTML.trim() : '';
        
        // Replace <br> tags with spaces
        text = text.replace(/<br\s*\/?>/gi, ' ').replace(/\s+/g, ' ').trim();
        
        if (value && text) {
          addressOptions.push({
            value: value,
            text: text
          });
        }
      });

      console.log('Extracted address options:', addressOptions);
    } catch (err) {
      console.error('Error extracting address options:', err);
    }

    return addressOptions;
  }

  /**
   * Replace the address options in the select element within the HTML string
   * @param {string} htmlString - The HTML string containing the select
   * @param {Array} addressOptions - Array of {value, text} objects
   * @returns {string} Updated HTML string
   */
  function replaceAddressOptions(htmlString, addressOptions) {
    try {
      // Find the select element and extract its existing attributes
      const selectMatch = htmlString.match(/<select([^>]*)>([\s\S]*?)<\/select>/);
      if (!selectMatch) {
        console.warn('Select element not found in template');
        return htmlString;
      }

      const selectAttributes = selectMatch[1];
      let newOptionsHtml = '<option value="">Sélectionner l\'adresse</option>';
      
      // Build new options from extracted addresses
      addressOptions.forEach((option, index) => {
        const selected = index === 0 ? ' selected="selected"' : '';
        newOptionsHtml += `<option value="${escapeHtml(option.value)}"${selected}>${escapeHtml(option.text)}</option>`;
      });

      // If no addresses found, keep a default option
      if (addressOptions.length === 0) {
        newOptionsHtml += '<option value="address_1" selected="selected">Default Address</option>';
      }

      // Replace the entire select element
      const newSelectHtml = `<select${selectAttributes}>${newOptionsHtml}</select>`;
      htmlString = htmlString.replace(/<select[^>]*>[\s\S]*?<\/select>/, newSelectHtml);

      // Update data-placeholder if we have addresses
      if (addressOptions.length > 0) {
        const firstAddressText = addressOptions[0].text;
        htmlString = htmlString.replace(
          /data-placeholder="[^"]*"/,
          `data-placeholder="${escapeHtml(firstAddressText)}"`
        );
      }

    } catch (err) {
      console.error('Error replacing address options:', err);
    }

    return htmlString;
  }
}
	
	


	
	

/**
 * Updates the hidden multi-shipping address data field
 */
function updateMultiShippingData() {
    const hiddenDataInput = document.querySelector('input.multi-shipping-adr-data');
    if (!hiddenDataInput) return;
    
    try {
        const currentData = JSON.parse(hiddenDataInput.value || '{}');
        const newData = {};
        
        // Only keep data for items that still exist in the DOM
        const existingRows = document.querySelectorAll('.multi-shipping-table tbody tr.main-pdct-tr');
        existingRows.forEach(row => {
            const quantityInput = row.querySelector('.multi-ship-pdct-qty');
            if (quantityInput) {
                const cartKey = quantityInput.getAttribute('data-cart_key');
                if (cartKey && currentData[cartKey]) {
                    newData[cartKey] = currentData[cartKey];
                }
            }
        });
        
        hiddenDataInput.value = JSON.stringify(newData);
        console.log('Updated multi-shipping data:', newData);
        
    } catch (e) {
        console.error('Error updating multi-shipping data:', e);
    }
}



/**
 * Advanced sync function that also handles visual updates
 */
function syncMultiShippingAdvanced() {
    syncMultiShippingWithOrderDetails();
    
    // Additional visual updates
    setTimeout(() => {
        // Force redraw of select2 elements if they exist
        if (typeof jQuery !== 'undefined' && jQuery.fn.select2) {
            jQuery('.thwma-cart-shipping-options').each(function() {
                if (jQuery(this).hasClass('select2-hidden-accessible')) {
                    jQuery(this).trigger('change.select2');
                }
            });
        }
        
        // Show/hide overlay
        const overlay = document.querySelector('.multi-shipping-table-overlay');
        if (overlay) {
            overlay.style.display = 'block';
            setTimeout(() => {
                overlay.style.display = 'none';
            }, 500);
        }
        
    }, 100);
}


// Export functions for manual use
window.syncMultiShipping = syncMultiShippingWithOrderDetails;
window.syncMultiShippingAdvanced = syncMultiShippingAdvanced; 

	
jQuery('body').on('updated_checkout', function() {
	console.log("calliing synccc updated_checkout")
	setTimeout(()=>{
		if(AFB_STATE.deliveryOption==="multiship"  ){
			syncMultiShippingWithOrderDetails()
		}
	}, 300)
	
});
	
jQuery('body').on('afb_order_review_updated', function() {
	console.log("calliing synccc afb_order_review_updated")
//     syncMultiShippingAdvanced();
	setTimeout(()=>{
		
		if(AFB_STATE.deliveryOption==="multiship"  ){
			syncMultiShippingWithOrderDetails()
		}
		
	},300)
	
});
	
	
jQuery('body').on('afb_update_multi_shipping', function() {
	console.log("calliing synccc afb_update_multi_shipping")
//     syncMultiShippingAdvanced();
	setTimeout(()=>{
// 		syncMultiShippingWithOrderDetails()
	}, 400)
	
});



	
	
	
	
	
	
	
	
	
/**
 * Set up automatic syncing when order details change
 */
// function setupAutoSync() {
//     // Prevent multiple setups
//     if (window.syncSetupComplete) {
//         return;
//     }
    
//     const orderReview = document.getElementById('afb-order-review');
//     if (!orderReview) {
//         console.warn('Order review section not found');
//         return;
//     }
    
//     // Flag to prevent loops
//     window.syncInProgress = false;
    
//     // Create observer for changes in order details
//     const observer = new MutationObserver((mutations) => {
//         // Prevent sync loops
//         if (window.syncInProgress) {
//             return;
//         }
        
//         let shouldSync = false;
        
//         mutations.forEach((mutation) => {
//             // Only sync on specific changes, avoid loops
//             if (mutation.type === 'childList') {
//                 // Check if items were added/removed (not just DOM manipulation)
//                 const addedNodes = Array.from(mutation.addedNodes);
//                 const removedNodes = Array.from(mutation.removedNodes);
                
//                 const hasOrderItemChanges = addedNodes.some(node => 
//                     node.nodeType === 1 && node.classList && node.classList.contains('afb-order-item')
//                 ) || removedNodes.some(node => 
//                     node.nodeType === 1 && node.classList && node.classList.contains('afb-order-item')
//                 );
                
//                 if (hasOrderItemChanges) {
//                     shouldSync = true;
//                 }
//             }
//         });
        
//         if (shouldSync) {
//             console.log('Order details changed, syncing...');
//             // Debounce the sync call
//             clearTimeout(window.syncTimeout);
//             window.syncTimeout = setTimeout(() => {
//                 if (!window.syncInProgress) {
//                     syncMultiShippingAdvanced();
//                 }
//             }, 500);
//         }
//     });
    
//     // Start observing with more specific settings
//     observer.observe(orderReview, {
//         childList: true,
//         subtree: true
//     });
    
//     // Manual quantity change handler (separate from auto-observer)
//     document.addEventListener('change', (e) => {
//         if (e.target.classList.contains('afb-quantity-input') && !window.syncInProgress) {
//             clearTimeout(window.syncTimeout);
//             window.syncTimeout = setTimeout(() => {
//                 if (!window.syncInProgress) {
//                     syncMultiShippingAdvanced();
//                 }
//             }, 500);
//         }
//     });
    
//     window.syncSetupComplete = true;
//     console.log('Auto-sync setup completed');
// }
	
	
	

// Initialize auto-sync when DOM is ready
// if (document.readyState === 'loading') {
//     document.addEventListener('DOMContentLoaded', setupAutoSync);
// } else {
//     setupAutoSync();
// }
</script>


















<script>

document.addEventListener("DOMContentLoaded", function () {
    const phoneSelector = "#user_phone, [name='billing_phone'], [name='shipping_phone'], [type='tel'],.afb-phones";

    // Load intl-tel-input dynamically
    const loadScript = (src, cb) => {
        const s = document.createElement("script");
        s.src = src;
        s.onload = cb;
        document.head.appendChild(s);
    };
    const loadCSS = (href) => {
        const l = document.createElement("link");
        l.rel = "stylesheet";
        l.href = href;
        document.head.appendChild(l);
    };

    loadCSS("https://cdn.jsdelivr.net/npm/intl-tel-input@25.10.11/build/css/intlTelInput.min.css");
    loadScript("https://cdn.jsdelivr.net/npm/intl-tel-input@25.10.11/build/js/intlTelInput.min.js", function () {

		 console.log("executeddd ")
		
        function initPhoneField(field) {
			 console.log("loaded", field) 
			
            if (!field || field.classList.contains("iti-initialized")) return;

            const iti = window.intlTelInput(field, {
                initialCountry: "il",
                nationalMode: false,
                separateDialCode: false,
                autoPlaceholder: "polite"
            });

            field.addEventListener("countrychange", function () {
                const code = "+" + iti.getSelectedCountryData().dialCode;
                if (!field.value.startsWith(code)) {
                    field.value = code + " ";
                    field.setSelectionRange(field.value.length, field.value.length);
                }
            });

            // Mark as initialized so we don’t re-init
            field.classList.add("iti-initialized");
        }

        // Initialize existing fields
        document.querySelectorAll(phoneSelector).forEach(initPhoneField);

        // Watch for dynamically added fields
        const observer = new MutationObserver(() => {
            document.querySelectorAll(phoneSelector).forEach(initPhoneField);
        });

        observer.observe(document.body, {
            childList: true,
            subtree: true
        });
    });
}); 
</script>

<style>
										
										.afb_phone{
/* 											padding-left: 48px !important; */
												text-indent: 48px !important
											}
										.iti {
										  width: 100% !important; /* Make it expand to container width */
										}

										.iti__country-list {
										  min-width: 300px !important; /* Control dropdown width */
										  max-height: 250px; /* Scroll instead of overflowing */
										  overflow-y: auto;
										}

										/* Adjust the selected flag button */
										.iti__flag-container {
										  width: auto !important;
										}
										
										[type="search"].iti__search-input{
											padding-left: 26px !important;
										}
	
	#payment{
		display: none !important
	}
									</style>


<script>
	
//select payment option 
(function () {
  function selectFirstPayment() {
    const firstPayment = document.querySelector('.wc_payment_methods input[type="radio"]');
    if (firstPayment && !firstPayment.checked) {
      firstPayment.checked = true;
      firstPayment.dispatchEvent(new Event('change', { bubbles: true }));
    }
  }

  // Run immediately and after checkout updates
  document.addEventListener('DOMContentLoaded', selectFirstPayment);
  jQuery(document.body).on('updated_checkout', selectFirstPayment);
})();

</script>


<?php
}